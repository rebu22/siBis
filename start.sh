#!/usr/bin/env bash
# ponytail: satu mesin, tanpa systemd. Kalau nanti perlu hidup setelah logout/reboot, pindah ke systemd.
set -euo pipefail
cd "$(dirname "$0")"

# Autostart GNOME tidak mewarisi PATH terminal; php ada di ~/.local/bin.
export PATH="$HOME/.local/bin:$PATH"

up() { curl -sf -m 2 "http://127.0.0.1:$1" >/dev/null 2>&1; }

# Dipakai setelah mengubah .env: hentikan layanan AI supaya konfigurasi baru terbaca.
if [ "${1:-}" = "--restart" ]; then
  for p in $(ps -eo pid,args | grep -E '[u]vicorn app:app' | awk '{print $1}'); do kill "$p" 2>/dev/null || true; done
  sleep 2
fi

up 8000        || { setsid nohup sh -c 'cd backend && exec php artisan serve --host=127.0.0.1 --port=8000' >/tmp/sibis.log 2>&1 </dev/null & echo "aplikasi: dinyalakan"; }
up 8001/health || { setsid nohup bash ai-service/run.sh >/tmp/rag.log 2>&1 </dev/null & echo "layanan AI: dinyalakan"; }

for p in 8000 8001/health; do
  for _ in $(seq 40); do up "$p" && break; sleep 1; done
  up "$p" || { echo "gagal menyalakan port $p, lihat /tmp/sibis.log atau /tmp/rag.log" >&2; exit 1; }
done

echo "aplikasi : http://127.0.0.1:8000"
echo "layanan AI: $(curl -s http://127.0.0.1:8001/health)"
