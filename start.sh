#!/usr/bin/env bash
# ponytail: satu mesin, tanpa systemd. Kalau nanti perlu hidup setelah logout/reboot, pindah ke systemd.
set -euo pipefail
cd "$(dirname "$0")"

# Autostart GNOME tidak mewarisi PATH terminal; php ada di ~/.local/bin.
export PATH="$HOME/.local/bin:$PATH"

up() { curl -sf -m 2 "http://127.0.0.1:$1" >/dev/null 2>&1; }
# Layanan AI dianggap hidup hanya bila /health menjawab JSON {"ok":true};
# server lain di port 8001 (mis. artisan serve) tidak lolos pemeriksaan ini.
ai_up() { curl -sf -m 2 http://127.0.0.1:8001/health 2>/dev/null | grep -q '"ok"'; }

# Dipakai setelah mengubah .env: hentikan layanan AI supaya konfigurasi baru terbaca.
if [ "${1:-}" = "--restart" ]; then
  for p in $(ps -eo pid,args | grep -E '[u]vicorn' | awk '{print $1}'); do kill "$p" 2>/dev/null || true; done
  sleep 2
fi

up 8000    || { setsid nohup sh -c 'cd backend && exec php artisan serve --host=127.0.0.1 --port=8000' >/tmp/sibis.log 2>&1 </dev/null & echo "aplikasi: dinyalakan"; }
ai_up      || { setsid nohup bash ai-service/run.sh >/tmp/rag.log 2>&1 </dev/null & echo "layanan AI: dinyalakan"; }

for _ in $(seq 40); do up 8000 && break; sleep 1; done
up 8000 || { echo "gagal menyalakan aplikasi di port 8000, lihat /tmp/sibis.log" >&2; exit 1; }

for _ in $(seq 40); do ai_up && break; sleep 1; done
if ! ai_up; then
  # Tanpa -f: 404 dari server lain tetap terdeteksi sebagai "port terpakai".
  curl -s -m 2 -o /dev/null http://127.0.0.1:8001/ \
    && echo "port 8001 dipakai proses lain (bukan layanan AI). Hentikan proses itu dulu." >&2
  echo "gagal menyalakan layanan AI di port 8001, lihat /tmp/rag.log" >&2
  exit 1
fi

echo "aplikasi : http://127.0.0.1:8000"
echo "layanan AI: $(curl -s http://127.0.0.1:8001/health)"
