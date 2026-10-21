#!/usr/bin/env bash
# ponytail: satu mesin, tanpa systemd. Kalau nanti perlu hidup setelah logout/reboot, pindah ke systemd.
set -euo pipefail
cd "$(dirname "$0")"

up() { curl -sf -m 2 "http://127.0.0.1:$1" >/dev/null 2>&1; }

up 8000        || { setsid nohup php artisan serve --host=127.0.0.1 --port=8000 >/tmp/sibis.log 2>&1 </dev/null & echo "aplikasi: dinyalakan"; }
up 8001/health || { setsid nohup bash service/run.sh >/tmp/rag.log 2>&1 </dev/null & echo "layanan AI: dinyalakan (Ollama ikut)"; }

for p in 8000 8001/health; do
  for _ in $(seq 40); do up "$p" && break; sleep 1; done
  up "$p" || { echo "gagal menyalakan port $p, lihat /tmp/sibis.log atau /tmp/rag.log" >&2; exit 1; }
done

echo "aplikasi : http://127.0.0.1:8000"
echo "layanan AI: $(curl -s http://127.0.0.1:8001/health)"
