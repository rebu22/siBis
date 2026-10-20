#!/usr/bin/env bash
# ponytail: peluncur satu mesin (Ollama + uvicorn). Ganti ke systemd/docker kalau service ini pindah host.
set -euo pipefail
cd "$(dirname "$0")"

REPO="$(cd .. && pwd)"
RAG_TOKEN="$(grep -E '^RAG_TOKEN=' "$REPO/.env" | cut -d= -f2- | tr -d '"')"
[ -n "$RAG_TOKEN" ] || { echo "RAG_TOKEN kosong di $REPO/.env" >&2; exit 1; }
export RAG_TOKEN

# Kalau .env sudah berisi LLM_* (mis. API gratis), pakai itu dan Ollama dilewati.
for k in LLM_BASE_URL LLM_API_KEY LLM_MODEL; do
  v="$(grep -E "^$k=" "$REPO/.env" | cut -d= -f2- | tr -d '"')"
  if [ -n "$v" ]; then export "$k=$v"; fi
done

OLLAMA="${OLLAMA_BIN:-$HOME/.ollama-dist/bin/ollama}"
export LLM_MODEL="${LLM_MODEL:-qwen2.5:3b}"
export LLM_BASE_URL="${LLM_BASE_URL:-http://127.0.0.1:11434/v1}"

if [ -z "${LLM_API_KEY:-}" ] && ! curl -sf http://127.0.0.1:11434/api/tags >/dev/null; then
  setsid nohup "$OLLAMA" serve >/tmp/ollama.log 2>&1 </dev/null &
  for _ in $(seq 30); do curl -sf http://127.0.0.1:11434/api/tags >/dev/null && break; sleep 1; done
  curl -sf http://127.0.0.1:11434/api/tags >/dev/null || { echo "Ollama gagal jalan, lihat /tmp/ollama.log" >&2; exit 1; }
fi

[ -n "${LLM_API_KEY:-}" ] || "$OLLAMA" show "$LLM_MODEL" >/dev/null 2>&1 || "$OLLAMA" pull "$LLM_MODEL"

exec .venv/bin/uvicorn app:app --host 127.0.0.1 --port 8001
