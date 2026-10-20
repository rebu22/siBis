"""Microservice RAG siBis (FastAPI).

Kontrak yang dipakai Laravel (app/Services/RagClient.php):

    POST /rag/ask    {question, history:[{role,content}]} -> {answer, sources:[{title,url,snippet}]}
    POST /rag/ingest {documents:[{id,text,title,url}]}    -> {ingested: int}

Aturan yang dipegang di sini:
- NFR-23: token internal wajib. Tanpa token yang cocok, jawab 401. Jalankan hanya di jaringan privat.
- NFR-12: prompt mewajibkan setiap jawaban merujuk konteks yang ditemukan. Bila tidak ada konteks,
  model diminta menyatakan keterbatasan itu, bukan mengarang dasar hukum.
- NFR-16: yang diterima di sini hanya teks pertanyaan; tidak ada identitas pengguna.
- NFR-17: tanpa LLM yang dikonfigurasi, jawab 503 supaya Laravel menampilkan pesan fallback.
"""

import os
from typing import Any

from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

INTERNAL_TOKEN = os.getenv("RAG_TOKEN", "")
LLM_BASE_URL = os.getenv("LLM_BASE_URL", "")      # endpoint kompatibel OpenAI (termasuk Ollama/vLLM)
LLM_API_KEY = os.getenv("LLM_API_KEY", "")
LLM_MODEL = os.getenv("LLM_MODEL", "")
CHROMA_PATH = os.getenv("CHROMA_PATH", "./chroma")
COLLECTION = os.getenv("CHROMA_COLLECTION", "hukum_bisnis")
TOP_K = int(os.getenv("RAG_TOP_K", "4"))

DISCLAIMER = "Jawaban ini bersifat edukasi, bukan nasihat hukum untuk kasus tertentu."

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


def _retrieve(question: str) -> list[dict[str, Any]]:
    collection = _collection()
    if collection is None:
        return []

    hits = collection.query(query_texts=[question], n_results=TOP_K)
    documents = (hits.get("documents") or [[]])[0]
    metadatas = (hits.get("metadatas") or [[]])[0]

    return [
        {"title": (meta or {}).get("title", ""), "url": (meta or {}).get("url", ""), "snippet": text[:400]}
        for text, meta in zip(documents, metadatas)
        if (meta or {}).get("title")
    ]


def _answer(question: str, history: list[Turn], sources: list[dict[str, Any]]) -> str:
    if not (LLM_BASE_URL and LLM_MODEL):
        raise HTTPException(status_code=503, detail="LLM belum dikonfigurasi")

    import httpx

    context = "\n\n".join(f"[{i + 1}] {s['title']}: {s['snippet']}" for i, s in enumerate(sources))
    system = (
        "Anda asisten hukum bisnis Indonesia. Aturan: (1) jawab hanya berdasarkan KONTEKS; "
        "(2) sebutkan dasar hukum yang benar-benar ada di konteks, jangan mengarang pasal; "
        "(3) jika konteks tidak memuat jawabannya, katakan terus terang bahwa rujukan belum tersedia; "
        "(4) jelas, ringkas, tanpa janji hasil; (5) sertakan kalimat penutup: " + DISCLAIMER
    )
    messages = [{"role": "system", "content": system}]
    messages += [{"role": t.role, "content": t.content} for t in history if t.role in {"user", "assistant"}]
    messages.append({"role": "user", "content": f"KONTEKS:\n{context or '(kosong)'}\n\nPERTANYAAN: {question}"})

    response = httpx.post(
        f"{LLM_BASE_URL.rstrip('/')}/chat/completions",
        headers={"Authorization": f"Bearer {LLM_API_KEY}"} if LLM_API_KEY else {},
        json={"model": LLM_MODEL, "messages": messages, "temperature": 0.2},
        timeout=60,
    )
    response.raise_for_status()

    return response.json()["choices"][0]["message"]["content"].strip()


@app.get("/health")
def health() -> dict[str, Any]:
    return {"ok": True, "llm": bool(LLM_BASE_URL and LLM_MODEL), "chroma": _collection() is not None}


@app.post("/rag/ask")
def ask(payload: AskRequest, authorization: str | None = Header(default=None)) -> dict[str, Any]:
    _auth(authorization)

    sources = _retrieve(payload.question)

    return {"answer": _answer(payload.question, payload.history, sources), "sources": sources}


@app.post("/rag/ingest")
def ingest(payload: IngestRequest, authorization: str | None = Header(default=None)) -> dict[str, Any]:
    _auth(authorization)

    collection = _collection()
    if collection is None:
        raise HTTPException(status_code=503, detail="ChromaDB tidak tersedia")

    collection.upsert(
        ids=[d.id for d in payload.documents],
        documents=[d.text for d in payload.documents],
        metadatas=[{"title": d.title, "url": d.url} for d in payload.documents],
    )

    return {"ingested": len(payload.documents)}
