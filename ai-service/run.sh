#!/usr/bin/env bash
# ponytail: peluncur layanan RAG. Model dipanggil lewat endpoint kompatibel OpenAI (LLM_* di backend/.env).
set -euo pipefail
cd "$(dirname "$0")"

REPO="$(cd .. && pwd)"
ENV_FILE="$REPO/backend/.env"
RAG_TOKEN="$(grep -E '^RAG_TOKEN=' "$ENV_FILE" | cut -d= -f2- | tr -d '"' || true)"
[ -n "$RAG_TOKEN" ] || { echo "RAG_TOKEN kosong di $ENV_FILE" >&2; exit 1; }
export RAG_TOKEN

for k in LLM_BASE_URL LLM_API_KEY LLM_MODEL LLM_FALLBACK_BASE_URL LLM_FALLBACK_API_KEY LLM_FALLBACK_MODEL; do
  v="$(grep -E "^$k=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' || true)"
  if [ -n "$v" ]; then export "$k=$v"; fi
done

[ -n "${LLM_API_KEY:-}" ] || { echo "LLM_API_KEY kosong di backend/.env — isi kunci penyedia model dulu." >&2; exit 1; }

[ -x .venv/bin/python ] || { echo "venv belum siap: python3 -m venv .venv && .venv/bin/pip install -r requirements.txt" >&2; exit 1; }
.venv/bin/python -c "import uvicorn" 2>/dev/null || { echo "dependensi venv belum lengkap: .venv/bin/pip install -r requirements.txt" >&2; exit 1; }

# Jalankan lewat modul, bukan skrip .venv/bin/uvicorn: shebang venv tidak ikut rusak saat folder di-rename.
exec .venv/bin/python -m uvicorn app:app --host 127.0.0.1 --port 8001
