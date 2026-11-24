"""Microservice RAG siBis (FastAPI).

Kontrak yang dipakai Laravel (backend/app/Services/RagClient.php):

    POST /rag/ask    {question, history:[{role,content}]} -> {answer, sources:[{title,url,snippet}]}
    POST /rag/ingest {documents:[{id,text,title,url}]}    -> {ingested: int}
    POST /rag/ingest/pdf  (multipart: file, title, url)   -> {id, title, chunks}
    GET  /rag/documents                                   -> [{id, title, chunks}]
    DELETE /rag/documents/{id}                            -> {deleted: int}

Aturan yang dipegang di sini:
- NFR-23: token internal wajib. Tanpa token yang cocok, jawab 401. Jalankan hanya di jaringan privat.
- NFR-12: prompt mewajibkan setiap jawaban merujuk konteks yang ditemukan. Bila tidak ada konteks,
  model diminta menyatakan keterbatasan itu, bukan mengarang dasar hukum.
- NFR-16: yang diterima di sini hanya teks pertanyaan; tidak ada identitas pengguna.
- NFR-17: tanpa LLM yang dikonfigurasi, jawab 503 supaya Laravel menampilkan pesan fallback.
"""

import io
import logging
import os
import re
import sqlite3
import time
import uuid
from typing import Any

from fastapi import FastAPI, File, Form, Header, HTTPException, UploadFile
from pydantic import BaseModel, Field

INTERNAL_TOKEN = os.getenv("RAG_TOKEN", "")
LLM_BASE_URL = os.getenv("LLM_BASE_URL", "")      # endpoint kompatibel OpenAI (termasuk Ollama/vLLM)
LLM_API_KEY = os.getenv("LLM_API_KEY", "")
LLM_MODEL = os.getenv("LLM_MODEL", "")
# Jalur cadangan (opsional). Kosongkan base URL untuk memakai penyedia yang sama dengan model lain.
LLM_FALLBACK_BASE_URL = os.getenv("LLM_FALLBACK_BASE_URL", "")
LLM_FALLBACK_API_KEY = os.getenv("LLM_FALLBACK_API_KEY", "")
LLM_FALLBACK_MODEL = os.getenv("LLM_FALLBACK_MODEL", "")
CHROMA_PATH = os.getenv("CHROMA_PATH", "./chroma")
COLLECTION = os.getenv("CHROMA_COLLECTION", "hukum_bisnis")
KB_PATH = os.getenv("KB_PATH", "./kb.sqlite3")    # indeks kata kunci, di samping direktori Chroma
TOP_K = int(os.getenv("RAG_TOP_K", "4"))

DISCLAIMER = "Jawaban ini bersifat edukasi, bukan nasihat hukum untuk kasus tertentu."

log = logging.getLogger("sibis.rag")

app = FastAPI(title="siBis RAG", docs_url=None, redoc_url=None)


class Turn(BaseModel):
    role: str
    content: str


class AskRequest(BaseModel):
    question: str = Field(min_length=3, max_length=1000)
    history: list[Turn] = Field(default_factory=list)


class Document(BaseModel):
    id: str
    text: str = Field(min_length=1)
    title: str = ""
    url: str = ""


class IngestRequest(BaseModel):
    documents: list[Document] = Field(min_length=1, max_length=200)


def _auth(token: str | None) -> None:
    if not INTERNAL_TOKEN or token != f"Bearer {INTERNAL_TOKEN}":
        raise HTTPException(status_code=401, detail="token internal tidak valid")


def _collection():
    """ChromaDB opsional: kalau belum terpasang, retrieval dilewati (jawaban tanpa sumber)."""
    try:
        import chromadb
    except ImportError:
        return None

    try:
        return chromadb.PersistentClient(path=CHROMA_PATH).get_or_create_collection(COLLECTION)
    except Exception:
        return None


def _hit(title: str, url: str, text: str) -> dict[str, Any]:
    return {
        "title": title,
        "url": url,
        "snippet": text[:400],
        # `passage` hanya untuk model; klien menerima `snippet` saja agar jawaban tidak membengkak.
        "passage": text,
    }

def _kb() -> sqlite3.Connection:
    """Indeks kata kunci.

    Embedding bawaan Chroma (all-MiniLM) hanya dilatih untuk bahasa Inggris, sehingga istilah
    hukum Indonesia sering tidak terambil. FTS5 bawaan sqlite menutup celah itu tanpa dependensi
    baru: hasilnya digabung dengan hasil vektor di `_retrieve`.
    """
    db = sqlite3.connect(KB_PATH)
    db.execute(
        "CREATE VIRTUAL TABLE IF NOT EXISTS chunks USING fts5("
        "id UNINDEXED, title UNINDEXED, url UNINDEXED, text)"
    )
    return db

def _keyword(question: str, limit: int = 8) -> list[dict[str, Any]]:
    # Token dibersihkan jadi alfanumerik saja supaya tidak bisa menyusup ke sintaks MATCH.
    terms = sorted({t for t in (re.sub(r"\W", "", w) for w in question.lower().split()) if len(t) > 2})
    if not terms:
        return []

    try:
        rows = _kb().execute(
            "SELECT title, url, text FROM chunks WHERE chunks MATCH ? ORDER BY bm25(chunks) LIMIT ?",
            [" OR ".join(terms), limit],
        ).fetchall()
    except sqlite3.Error:
        return []

    return [_hit(title, url, text) for title, url, text in rows]

def _vector(question: str, limit: int = 8) -> list[dict[str, Any]]:
    collection = _collection()
    if collection is None:
        return []

    hits = collection.query(query_texts=[question], n_results=limit)
    documents = (hits.get("documents") or [[]])[0]
    metadatas = (hits.get("metadatas") or [[]])[0]

    return [
        _hit((meta or {}).get("title", ""), (meta or {}).get("url", ""), text)
        for text, meta in zip(documents, metadatas)
        if (meta or {}).get("title")
    ]

def _retrieve(question: str) -> list[dict[str, Any]]:
    """Gabung dua peringkat (reciprocal rank fusion): potongan yang ditemukan kedua cara naik."""
    ranked: dict[str, list[Any]] = {}
    for hits in (_vector(question), _keyword(question)):
        for rank, hit in enumerate(hits, 1):
            entry = ranked.setdefault(hit["passage"][:80], [0.0, hit])
            entry[0] += 1 / (60 + rank)

    return [hit for _, hit in sorted(ranked.values(), key=lambda e: -e[0])[:TOP_K]]


def _routes() -> list[tuple[str, str, str]]:
    """Jalur model berurutan: utama, lalu cadangan (penyedia lain atau model lain)."""
    routes = [(LLM_BASE_URL, LLM_API_KEY, LLM_MODEL)]

    if LLM_FALLBACK_MODEL:
        routes.append((
            LLM_FALLBACK_BASE_URL or LLM_BASE_URL,
            LLM_FALLBACK_API_KEY or LLM_API_KEY,
            LLM_FALLBACK_MODEL,
        ))

    return [route for route in routes if route[0] and route[2]]

def _answer(question: str, history: list[Turn], sources: list[dict[str, Any]]) -> str:
    if not (LLM_BASE_URL and LLM_MODEL):
        raise HTTPException(status_code=503, detail="LLM belum dikonfigurasi")

    import httpx

    context = "\n\n".join(
        f"[{i + 1}] {s['title']}: {s.get('passage') or s['snippet']}" for i, s in enumerate(sources)
    )
    system = (
        "Anda asisten siBis, pendamping hukum bisnis untuk pemilik usaha kecil di Indonesia. "
        "KONTEKS berisi potongan peraturan yang mungkin relevan.\n"
        "Aturan:\n"
        "1. Pertanyaan hukum: jawab berdasarkan KONTEKS dan tulis nomor rujukannya seperti [1] "
        "setiap kali menyebut dasar hukum.\n"
        "2. Sapaan, obrolan, atau pertanyaan tentang kemampuan Anda: jawab wajar dan singkat, "
        "tidak perlu memakai KONTEKS.\n"
        "3. Jangan mengarang pasal, angka, atau nama peraturan yang tidak ada di KONTEKS.\n"
        "4. Jika pertanyaan hukum tidak terjawab KONTEKS, katakan terus terang dan sebutkan "
        "peraturan yang tersedia.\n"
        "5. Jangan membuka jawaban dengan sapaan atau perkenalan diri; langsung ke inti.\n"
        "6. Ringkas, tanpa janji hasil. Bila jawaban memuat dasar hukum, tutup dengan: " + DISCLAIMER
    )
    messages = [{"role": "system", "content": system}]
    messages += [{"role": t.role, "content": t.content} for t in history if t.role in {"user", "assistant"}]
    messages.append({"role": "user", "content": f"KONTEKS:\n{context or '(kosong)'}\n\nPERTANYAAN: {question}"})

    last_error: str = "tidak ada respons"
    # Laravel memutus di RAG_TIMEOUT (60 dtk); berhenti sebelum itu agar jawaban cadangan tetap terkirim.
    deadline = time.monotonic() + 45

    # Penyedia gratis sering membalas 429/503 sesaat: coba ulang, lalu jalur cadangan.
    for base_url, api_key, model in _routes():
        for attempt in range(3):
            if time.monotonic() >= deadline:
                break
            try:
                response = httpx.post(
                    f"{base_url.rstrip('/')}/chat/completions",
                    headers={"Authorization": f"Bearer {api_key}"} if api_key else {},
                    json={"model": model, "messages": messages, "temperature": 0.2, "max_tokens": 400},
                    timeout=min(20.0, max(5.0, deadline - time.monotonic())),
                )

                if response.status_code in (429, 500, 502, 503, 504):
                    last_error = f"{model}: HTTP {response.status_code} {response.text[:160]}"
                    log.warning("LLM sibuk, percobaan %s: %s", attempt + 1, last_error)
                    if attempt < 2:
                        time.sleep(2 * (attempt + 1))
                    continue

                response.raise_for_status()

                return response.json()["choices"][0]["message"]["content"].strip()
            except httpx.HTTPStatusError as e:
                last_error = f"{model}: HTTP {e.response.status_code} {e.response.text[:160]}"
                break
            except httpx.HTTPError as e:
                last_error = f"{model}: {e!r}"
                if attempt < 2:
                    time.sleep(2 * (attempt + 1))

    log.error("LLM gagal setelah percobaan ulang: %s", last_error)
    raise HTTPException(status_code=503, detail="Model AI sedang sibuk. Coba lagi sebentar lagi.")


def _llm_ok() -> bool:
    """Ping nyata: konfigurasi terisi belum tentu penjawabnya hidup."""
    if not (LLM_BASE_URL and LLM_MODEL):
        return False
    try:
        import httpx

        response = httpx.get(
            f"{LLM_BASE_URL.rstrip('/')}/models",
            headers={"Authorization": f"Bearer {LLM_API_KEY}"} if LLM_API_KEY else {},
            timeout=3,
        )
        return response.status_code < 400
    except Exception:
        return False


@app.get("/health")
def health() -> dict[str, Any]:
    return {
        "ok": True,
        "llm": _llm_ok(),
        "fallback": bool(LLM_FALLBACK_MODEL or LLM_FALLBACK_BASE_URL),
        "chroma": _collection() is not None,
    }


@app.post("/rag/ask")
def ask(payload: AskRequest, authorization: str | None = Header(default=None)) -> dict[str, Any]:
    _auth(authorization)

    sources = _retrieve(payload.question)

    return {"answer": _answer(payload.question, payload.history, sources), "sources": sources}


@app.post("/rag/ingest")
def ingest(payload: IngestRequest, authorization: str | None = Header(default=None)) -> dict[str, Any]:
    _auth(authorization)
    _store([d.model_dump() for d in payload.documents])

    return {"ingested": len(payload.documents)}

@app.post("/rag/ingest/pdf")
async def ingest_pdf(
    file: UploadFile = File(...),
    title: str = Form(...),
    url: str = Form(""),
    authorization: str | None = Header(default=None),
) -> dict[str, Any]:
    """Unggah PDF dari CMS admin: ekstrak teks di sini, bukan di PHP (tidak ada parser PDF di PHP)."""
    _auth(authorization)

    try:
        import pypdf

        raw = "".join(
            (page.extract_text() or "")
            for page in pypdf.PdfReader(io.BytesIO(await file.read())).pages
        )
    except Exception:
        raise HTTPException(status_code=422, detail="PDF tidak bisa dibaca.")

    parts = _chunks(raw)
    if not parts:
        raise HTTPException(status_code=422, detail="PDF tidak memuat teks yang bisa dibaca (hasil pindai?).")

    doc_id = uuid.uuid4().hex
    _store([{"id": f"{doc_id}-{n:04d}", "text": t, "title": title, "url": url}
            for n, t in enumerate(parts)])

    return {"id": doc_id, "title": title, "chunks": len(parts)}

@app.get("/rag/documents")
def documents(authorization: str | None = Header(default=None)) -> list[dict[str, Any]]:
    """Daftar dokumen diturunkan dari id potongan (<doc_id>-<nnnn>), jadi tidak perlu tabel terpisah."""
    _auth(authorization)

    grouped: dict[str, dict[str, Any]] = {}
    for cid, title in _kb().execute("SELECT id, title FROM chunks").fetchall():
        doc_id = cid.rsplit("-", 1)[0]
        grouped.setdefault(doc_id, {"id": doc_id, "title": title, "chunks": 0})["chunks"] += 1

    return sorted(grouped.values(), key=lambda d: d["title"])

@app.delete("/rag/documents/{doc_id}")
def delete_document(doc_id: str, authorization: str | None = Header(default=None)) -> dict[str, int]:
    _auth(authorization)

    doc_id = re.sub(r"\W", "", doc_id)
    if not doc_id:
        raise HTTPException(status_code=422, detail="id dokumen tidak valid")

    pattern = f"{doc_id}-%"
    db = _kb()
    ids = [row[0] for row in db.execute("SELECT id FROM chunks WHERE id LIKE ?", (pattern,)).fetchall()]

    collection = _collection()
    if collection is not None and ids:
        collection.delete(ids=ids)

    db.execute("DELETE FROM chunks WHERE id LIKE ?", (pattern,))
    db.commit()
    db.close()

    return {"deleted": len(ids)}

SIZE, OVERLAP, BATCH = 800, 120, 200

def _chunks(text: str) -> list[str]:
    text = re.sub(r"\s+", " ", text).strip()

    return [p for p in (text[i:i + SIZE] for i in range(0, len(text), SIZE - OVERLAP)) if len(p) > 200]

def _store(documents: list[dict[str, Any]]) -> None:
    """Tulis ke Chroma + indeks kata kunci. Idempoten: id yang sama diganti, bukan diduplikasi."""
    collection = _collection()
    if collection is None:
        raise HTTPException(status_code=503, detail="ChromaDB tidak tersedia")

    db = _kb()
    for i in range(0, len(documents), BATCH):
        batch = documents[i:i + BATCH]
        ids = [d["id"] for d in batch]

        collection.upsert(
            ids=ids,
            documents=[d["text"] for d in batch],
            metadatas=[{"title": d["title"], "url": d["url"]} for d in batch],
        )

        db.execute(f"DELETE FROM chunks WHERE id IN ({','.join('?' * len(ids))})", ids)
        db.executemany(
            "INSERT INTO chunks VALUES (?, ?, ?, ?)",
            [(d["id"], d["title"], d["url"], d["text"]) for d in batch],
        )

    db.commit()
    db.close()
