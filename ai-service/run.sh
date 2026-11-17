#!/usr/bin/env bash
# ponytail: peluncur layanan RAG. Model dipanggil lewat endpoint kompatibel OpenAI (LLM_* di backend/.env).
set -euo pipefail
cd "$(dirname "$0")"

REPO="$(cd .. && pwd)"
ENV_FILE="$REPO/backend/.env"
RAG_TOKEN="$(grep -E '^RAG_TOKEN=' "$ENV_FILE" | cut -d= -f2- | tr -d '"' || true)"
[ -n "$RAG_TOKEN" ] || { echo "RAG_TOKEN kosong di $ENV_FILE" >&2; exit 1; }
export RAG_TOKEN

for k in LLM_BASE_URL LLM_API_KEY LLM_MODEL; do
  v="$(grep -E "^$k=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' || true)"
  if [ -n "$v" ]; then export "$k=$v"; fi
done

[ -n "${LLM_API_KEY:-}" ] || { echo "LLM_API_KEY kosong di backend/.env — isi kunci penyedia model dulu." >&2; exit 1; }

exec .venv/bin/uvicorn app:app --host 127.0.0.1 --port 8001
