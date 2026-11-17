# siBis — Sistem Informasi Konsultasi Hukum Bisnis Berbasis AI

Aplikasi web untuk pelaku usaha di Indonesia: konsultasi hukum bisnis awal
(perpajakan, perizinan berusaha, pendirian badan usaha) dengan jawaban AI yang
selalu menyertakan rujukan regulasi, ditambah perpustakaan kebijakan PDF,
riwayat konsultasi, langganan premium, dan kanal bantuan.

> Jawaban AI bukan pengganti advokat. Setiap jawaban menyertakan sumber dan
> peringatan bahwa pengguna tetap perlu berkonsultasi dengan ahli hukum.

## Tumpukan

| Bagian | Teknologi |
|---|---|
| `backend/` | Laravel 13, Inertia, session auth, SQLite/MySQL |
| `frontend/` | Vue 3, Vite, Tailwind CSS, Plus Jakarta Sans |
| `ai-service/` | FastAPI, ChromaDB (opsional), endpoint LLM kompatibel OpenAI |
| Pembayaran | Midtrans Snap (sandbox default) |
| Pengujian | PHPUnit (69 test) |

## Struktur repositori

```
siBis/
├── backend/        Laravel: app, routes, database, tests, public
├── frontend/       Vue + Vite: src/Pages, src/Layouts, src/Components, src/css
├── ai-service/     FastAPI: app.py, chroma/, kb.sqlite3, run.sh
├── docs/           SRS baru, gap analysis, DFD, panduan operasional & Midtrans
├── start.sh        menyalakan backend + ai-service sekaligus
├── README.md
└── ROADMAP.md
```

## Menjalankan di komputer sendiri

Butuh PHP 8.3, Composer, Node.js 20+, Python 3.11+ (untuk layanan AI), dan
ekstensi SQLite.

### 1. Backend

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Buka <http://localhost:8000>.

### 2. Frontend

```sh
cd frontend
npm install
npm run build     # produksi
# atau: npm run dev   (hot reload, jalankan bersamaan dengan php artisan serve)
```

Hasil build masuk ke `backend/public/build`; Laravel membacanya otomatis.

### 3. Layanan AI (opsional)

```sh
cd ai-service
python -m venv .venv
.venv/bin/pip install -r requirements.txt
./run.sh
```

`run.sh` membaca `RAG_TOKEN`, `LLM_BASE_URL`, `LLM_API_KEY`, dan `LLM_MODEL`
dari `backend/.env`. Tanpa layanan AI, aplikasi tetap berjalan dan menampilkan
pesan fallback `Layanan konsultasi sementara tidak tersedia.`

Alternatif satu perintah dari akar repo: `./start.sh` (tambahkan `--restart`
setelah mengubah `backend/.env`).

### Akun demo

Dibuat oleh `php artisan migrate --seed`. Password keduanya `password` —
**ganti sebelum dipakai di luar lokal**.

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@sibis.test` | `password` |
| Pengguna | `usaha@sibis.test` | `password` |

### Email

Pendaftaran mengirim email verifikasi. Isi `MAIL_*` di `backend/.env`; contoh
Gmail ada di `.env.example` (butuh App Password 16 karakter). Bawaan
`MAIL_MAILER=failover`: kalau SMTP gagal, pesan jatuh ke
`backend/storage/logs/laravel.log` dan pendaftaran tetap berhasil.

## Alur pengguna

1. **Beranda** `/` — profil perusahaan (visi, misi, layanan, kontak) dan
   kebijakan terbaru.
2. **Kebijakan** `/kebijakan` — daftar regulasi PDF, cari lewat `q`, saring
   kategori; detail di `/kebijakan/{slug}`.
3. **Daftar & verifikasi** `/register` → tautan verifikasi email.
4. **Masuk** `/login`, lupa password `/forgot-password`.
5. **Konsultasi** `/konsultasi` — kuota gratis 5 pertanyaan/hari; jawaban
   menyertakan sumber. Kuota tidak dipotong bila layanan AI mati.
6. **Riwayat** `/riwayat` — buka, hapus satu, atau hapus semua percakapan.
   Setiap jawaban bisa dinilai (1–5) atau dilaporkan tidak akurat.
7. **Premium** `/premium` — 100 pertanyaan/hari selama 30 hari, bayar lewat
   Midtrans (QRIS/VA/e-wallet). Aktif otomatis setelah pembayaran lunas.
8. **Bantuan** — `/lapor-bug` (boleh tanpa akun) dan `/hubungi-admin`
   (wajib login).
9. **Profil** `/profile` — ubah data, ganti password, atau hapus akun beserta
   seluruh datanya.

## Alur admin

1. Login dengan akun berperan `admin`, lalu buka `/admin`.
2. Verifikasi OTP: kode 6 digit berlaku 5 menit, maksimal 5 percobaan; sesi
   admin berlaku 30 menit sebelum diminta lagi.
3. Menu admin:
   - `/admin/pengguna` — paket, kuota hari ini, status verifikasi.
   - `/admin/kebijakan` — unggah/ubah/hapus PDF, indeks ulang ke basis
     pengetahuan chatbot.
   - `/admin/profil` — edit visi, misi, layanan, dan kontak publik.
   - `/admin/laporan` — tindak lanjut laporan jawaban AI.
   - `/admin/laporan-bug` — laporan bug + screenshot.
   - `/admin/pesan` — kotak masuk pesan pengguna.
   - `/admin/pembayaran` — transaksi Midtrans & pendapatan lunas.
   - `/admin/audit` — jejak perubahan konten (metadata saja).

Menjadikan akun lain admin:

```sh
cd backend
php artisan tinker --execute='\App\Models\User::where("email","alamat@contoh.com")->first()->forceFill(["role"=>"admin"])->save();'
```

## Midtrans

Panduan lengkap: [`docs/MIDTRANS.md`](docs/MIDTRANS.md). Ringkasnya:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_PREMIUM_PRICE=49000
MIDTRANS_PREMIUM_DAYS=30
```

Tanpa `MIDTRANS_SERVER_KEY`, tombol bayar otomatis nonaktif dan tidak ada
transaksi yang dibuat.

## Operasional

Panduan lengkap: [`docs/OPERASIONAL.md`](docs/OPERASIONAL.md).

```sh
cd backend
php artisan sibis:backup    # snapshot DB + prune retensi 30 hari
php artisan sibis:health    # cek DB dan layanan RAG
```

Scheduler (cron server):

```cron
* * * * * cd /path/ke/siBis/backend && php artisan schedule:run >> /dev/null 2>&1
```

Backup harian 02:30, health check tiap 5 menit. Metrik LLM ditulis ke
`backend/storage/logs/metrics-*.log` (tanpa isi pertanyaan).

## Pengujian

```sh
cd backend
php artisan test
```

69 test mencakup auth, kuota, IDOR riwayat, injeksi pencarian, CMS, premium,
webhook Midtrans, bug/pesan, audit log, backup, PII, dan health check.

## Dokumentasi

| Berkas | Isi |
|---|---|
| `docs/SRS_siBis.md` | SRS baru sesuai implementasi final |
| `docs/GAP_ANALYSIS.md` | Riwayat audit SRS lama vs kode, checklist RF/UC/NFR |
| `docs/DFD.md` | DFD Level 0–2 (Mermaid) |
| `docs/MIDTRANS.md` | Setup & alur pembayaran |
| `docs/OPERASIONAL.md` | Backup, metrik, health check, PII |
| `SRS_11241029.pdf` | SRS lama (arsip) |
