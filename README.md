# siBis — Sistem Informasi Konsultasi Hukum Bisnis

Company profile + chatbot konsultasi awal hukum bisnis untuk pelaku usaha di Indonesia
(perpajakan, perizinan berusaha, pendirian badan usaha). Setiap jawaban wajib menyertakan
dasar hukum dan disclaimer bahwa ini bukan pengganti advokat.

Dokumen acuan: `SRS_11241029.pdf` (sumber kebenaran), `SDD_siBis_11241029.docx`,
diagram `Usecase_siBis.drawio.png` dan `docs/DFD.md`.

Tumpukan: Laravel + Inertia + Vue 3 + Tailwind, basis data SQLite, dan microservice
Python FastAPI opsional di `service/`.

## Menjalankan di komputer sendiri

Butuh PHP 8.3, Composer, Node.js, dan ekstensi SQLite.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Buka `http://localhost:8000`.

Untuk pengembangan tampilan, `npm run dev` lebih nyaman daripada `npm run build`
(perubahan Vue/CSS langsung terlihat tanpa build ulang).

### Akun demo

Dibuat oleh `php artisan migrate --seed`. Password keduanya `password` — **ganti
sebelum dipakai di luar lokal**.

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@sibis.test` | `password` |
| Pengguna | `usaha@sibis.test` | `password` |

### Email

Pendaftaran mengirim email verifikasi ke alamat yang diisi pengguna, jadi `MAIL_*` di `.env`
harus diisi lebih dulu. Contoh untuk Gmail ada di `.env.example` (butuh App Password Gmail
16 karakter, bukan password akun biasa).

Bawaan `MAIL_MAILER=failover` artinya kalau SMTP gagal, pengiriman jatuh ke log: pendaftaran
tetap berhasil, tetapi email tidak sampai ke inbox. Isi email yang gagal bisa dilihat di
`storage/logs/laravel.log`.

### Layanan AI (opsional)

Halaman konsultasi memanggil microservice Python di `service/` lewat `RAG_URL`
(bawaan `http://127.0.0.1:8001`). Kalau layanan itu tidak dinyalakan, aplikasi tetap jalan
dan menampilkan pesan fallback — tidak error.

## Alur pemakaian pengguna

1. **Beranda** `/` — profil perusahaan: visi, misi, layanan, kontak, dan artikel terbaru.
2. **Artikel** `/artikel` — daftar artikel terbit, bisa dicari lewat kata kunci (`q`) dan
   disaring per kategori. Detail artikel di `/artikel/{slug}`.
3. **Daftar** `/register` — isi nama, email, password, nama usaha, dan kota. Setelah berhasil,
   pengguna otomatis login dan diarahkan ke konsultasi.
4. **Verifikasi email** — buka tautan di email yang dikirim saat daftar
   (`/verify-email/{id}/{hash}`). Tautan terikat ke host saat dikirim, jadi harus dibuka lewat
   alamat yang sama dengan saat mendaftar. Kalau belum masuk, tombol **Kirim ulang email
   verifikasi** ada di halaman itu.
5. **Masuk** `/login` — setelah login langsung ke halaman konsultasi. Lupa password lewat
   `/forgot-password`, yang mengirim tautan reset berbatas waktu.
6. **Konsultasi** `/konsultasi` — tulis pertanyaan, jawaban AI muncul beserta dasar hukum dan
   disclaimer, lalu tersimpan otomatis ke riwayat.
7. **Riwayat** `/riwayat` — daftar sesi konsultasi; `/riwayat/{id}` untuk membuka percakapan.
   Tiap jawaban bisa dinilai atau dilaporkan sebagai tidak akurat.
8. **Profil** `/profile` — ubah data usaha dan kontak, ganti password, atau hapus akun
   beserta seluruh datanya.

## Alur admin

1. Masuk memakai akun berperan `admin` (mis. `admin@sibis.test`).
2. Membuka `/admin` akan mengarahkan ke `/admin/verifikasi`. Klik kirim kode, lalu lihat kode
   6 digit yang dikirim ke email admin.
3. Kode berlaku **5 menit**, maksimal **5 percobaan**, dan kode lama langsung tidak berlaku saat
   kode baru diminta. Setelah lolos, verifikasi berlaku **30 menit** sebelum diminta lagi.
4. Dashboard admin menampilkan jumlah pengguna, sesi chat, pesan, laporan berstatus `baru`,
   jumlah artikel, dan 10 laporan terbaru.

5. **Pengguna** `/admin/pengguna` — daftar akun beserta paket, pemakaian kuota hari ini,
   status verifikasi, dan jumlah percakapan. Bisa dicari lewat nama atau email.
6. **Artikel** `/admin/artikel` — tulis, ubah, terbitkan, dan hapus artikel. Slug dibuat
   otomatis dari judul dan dibuat unik; artikel berstatus draf tidak tampil di `/artikel`.

Yang belum dibangun: unggah PDF regulasi ke basis pengetahuan, monitoring pembayaran premium,
laporan bug, dan pesan pengguna ke admin. Bagian itu sengaja tidak diberi tombol supaya tidak
menyesatkan.

Untuk menjadikan akun lain sebagai admin:

```sh
php artisan tinker --execute='\App\Models\User::where("email","alamat@contoh.com")->first()->forceFill(["role"=>"admin"])->save();'
```

## Struktur singkat

| Jalur | Isi |
|---|---|
| `app/Http/Controllers` | controller web (Home, Article, Consultation, Profile, Admin) |
| `app/Http/Middleware` | `EnsureAdmin` (role + OTP) dan `SecurityHeaders` (CSP ber-nonce) |
| `app/Models` | User, Article, CompanyProfile, ChatSession, ChatMessage, AnswerReport, OtpCode |
| `resources/js/Pages` | halaman Inertia/Vue |
| `service/` | microservice RAG FastAPI (opsional) |
| `docs/` | DFD dan catatan rancangan |
