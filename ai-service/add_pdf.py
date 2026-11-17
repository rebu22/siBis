"""Tambahkan satu PDF peraturan ke basis pengetahuan.

Contoh:
    cd ai-service && .venv/bin/python add_pdf.py ~/Downloads/pp-2024.pdf \
        "PP No. 28 Tahun 2024 — Judul" https://peraturan.go.id/files/pp-2024.pdf

Butuh: RAG_TOKEN di backend/.env, layanan hidup di 127.0.0.1:8001, `pypdf` terpasang.
Kalau PDF diganti isinya, jalankan ulang; id potongan sama, jadi tidak menumpuk.
"""

import re
import sys
from pathlib import Path

import httpx
import pypdf

SIZE, OVERLAP, BATCH = 800, 120, 200
URL = "http://127.0.0.1:8001/rag/ingest"
ENV = Path(__file__).resolve().parents[1] / "backend" / ".env"


def chunks(text: str) -> list[str]:
    text = re.sub(r"\s+", " ", text).strip()
    parts, i = [], 0
    while i < len(text):
        parts.append(text[i:i + SIZE])
        i += SIZE - OVERLAP
    return [p for p in parts if len(p) > 200]


def main() -> None:
    if len(sys.argv) < 3:
        sys.exit(__doc__)

    path, title = Path(sys.argv[1]).expanduser(), sys.argv[2]
    url = sys.argv[3] if len(sys.argv) > 3 else ""
    slug = re.sub(r"\W+", "-", path.stem.lower())

    raw = "".join((page.extract_text() or "") for page in pypdf.PdfReader(path).pages)
    docs = [
        {"id": f"{slug}-{n:04d}", "text": text, "title": title, "url": url}
        for n, text in enumerate(chunks(raw))
    ]
    if not docs:
        sys.exit("PDF tidak memuat teks yang bisa dibaca (hasil pindai?).")

    token = next(l.split("=", 1)[1].strip().strip('"') for l in ENV.read_text().splitlines()
                 if l.startswith("RAG_TOKEN="))

    for i in range(0, len(docs), BATCH):
        httpx.post(URL, headers={"Authorization": f"Bearer {token}"},
                   json={"documents": docs[i:i + BATCH]}, timeout=600).raise_for_status()

    print(f"{len(docs)} potongan dari {path.name} masuk ke basis pengetahuan.")


if __name__ == "__main__":
    main()
