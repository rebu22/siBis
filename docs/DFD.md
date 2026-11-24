# Data Flow Diagram siBis

Diagram ini menggambarkan implementasi final (bukan rancangan awal). Notasi
mengikuti Yourdon/DeMarco: proses (lingkaran), entitas eksternal (kotak),
data store (garis sejajar), dan arus data (panah berlabel).

Sumber kebenaran: `backend/routes/web.php`, `backend/app/Http/Controllers/*`,
`backend/app/Models/*`, dan `ai-service/app.py`.

---

## 1. DFD Level 0 — Diagram Konteks

siBis sebagai satu proses besar yang bertukar data dengan lima entitas luar.

```mermaid
flowchart LR
    PENGUNJUNG["Pengunjung<br/>(belum login)"]
    PENGGUNA["Pengguna<br/>(terdaftar)"]
    ADMIN["Admin"]
    MIDTRANS["Midtrans<br/>(Snap & API status)"]
    LLM["Penyedia LLM<br/>(endpoint OpenAI-compatible)"]

    SIBIS(("0<br/>siBis"))

    PENGUNJUNG -->|"pendaftaran, verifikasi email,<br/>laporan bug, pencarian kebijakan"| SIBIS
    SIBIS -->|"profil perusahaan, daftar kebijakan,<br/>PDF, status laporan"| PENGUNJUNG

    PENGGUNA -->|"pertanyaan konsultasi, rating,<br/>laporan jawaban, pesan admin,<br/>pembelian premium"| SIBIS
    SIBIS -->|"jawaban + rujukan, riwayat,<br/>kuota, status langganan"| PENGGUNA

    ADMIN -->|"login + OTP, CRUD kebijakan,<br/>edit profil, tindak lanjut laporan"| SIBIS
    SIBIS -->|"dasbor, daftar pengguna, laporan,<br/>pesan, transaksi, audit log"| ADMIN

    SIBIS -->|"permintaan transaksi Snap,<br/>cek status order"| MIDTRANS
    MIDTRANS -->|"token & redirect URL,<br/>webhook notifikasi pembayaran"| SIBIS

    SIBIS -->|"pertanyaan teredaksi + riwayat,<br/>dokumen PDF untuk indeks"| LLM
    LLM -->|"jawaban generatif"| SIBIS
```

Catatan: layanan RAG (FastAPI) adalah bagian internal sistem, sehingga tidak
digambar sebagai entitas luar. Batas kepercayaan yang sebenarnya adalah
Laravel → FastAPI (token internal) dan FastAPI → penyedia LLM.

---

## 2. DFD Level 1 — Proses Utama

```mermaid
flowchart TB
    PENGUNJUNG["Pengunjung"]
    PENGGUNA["Pengguna"]
    ADMIN["Admin"]
    MIDTRANS["Midtrans"]
    LLM["Penyedia LLM"]

    P1(("1.0<br/>Autentikasi<br/>& Akun"))
    P2(("2.0<br/>Konsultasi AI"))
    P3(("3.0<br/>Perpustakaan<br/>Kebijakan"))
    P4(("4.0<br/>Premium &<br/>Pembayaran"))
    P5(("5.0<br/>Bantuan &<br/>Pesan"))
    P6(("6.0<br/>CMS &<br/>Administrasi"))

    D1[("D1 users")]
    D2[("D2 business_profiles")]
    D3[("D3 chat_sessions /<br/>chat_messages")]
    D4[("D4 regulations")]
    D5[("D5 company_profiles")]
    D6[("D6 answer_ratings /<br/>answer_reports")]
    D7[("D7 transactions")]
    D8[("D8 bug_reports /<br/>contact_messages")]
    D9[("D9 audit_logs /<br/>otp_codes")]
    D10[("D10 ChromaDB +<br/>kb.sqlite3")]

    %% Autentikasi
    PENGUNJUNG -->|"nama, email, password"| P1
    P1 -->|"email verifikasi / reset"| PENGUNJUNG
    P1 <-->|"akun, role, plan, kuota"| D1
    P1 <-->|"data usaha"| D2
    P1 <-->|"OTP hash"| D9

    %% Konsultasi
    PENGGUNA -->|"pertanyaan"| P2
    P2 -->|"jawaban + sumber + disclaimer"| PENGGUNA
    P2 <-->|"sesi & pesan"| D3
    P2 -->|"pertanyaan teredaksi + riwayat"| LLM
    LLM -->|"jawaban"| P2
    P2 -->|"query retrieval"| D10
    D10 -->|"potongan regulasi"| P2
    PENGGUNA -->|"rating / laporan jawaban"| P2
    P2 <-->|"rating & laporan"| D6

    %% Kebijakan
    PENGUNJUNG -->|"kata kunci / kategori"| P3
    P3 -->|"daftar & PDF kebijakan"| PENGUNJUNG
    P3 <-->|"metadata & berkas PDF"| D4

    %% Premium
    PENGGUNA -->|"minta checkout"| P4
    P4 -->|"redirect halaman bayar"| PENGGUNA
    P4 <-->|"order, status, nominal"| D7
    P4 -->|"buat transaksi Snap"| MIDTRANS
    MIDTRANS -->|"token, redirect URL,<br/>webhook"| P4
    P4 -->|"perpanjang plan/premium_until"| D1

    %% Bantuan
    PENGUNJUNG -->|"laporan bug + screenshot"| P5
    PENGGUNA -->|"pesan ke admin"| P5
    P5 <-->|"bug & pesan"| D8
    P5 -->|"konfirmasi terkirim"| PENGUNJUNG
    P5 -->|"konfirmasi terkirim"| PENGGUNA

    %% Admin
    ADMIN -->|"kredensial + OTP"| P6
    P6 -->|"dasbor & daftar data"| ADMIN
    P6 <-->|"akun & kuota"| D1
    P6 <-->|"kebijakan + indeks RAG"| D4
    P6 -->|"unggah / hapus dokumen"| D10
    P6 <-->|"profil perusahaan"| D5
    P6 <-->|"laporan jawaban"| D6
    P6 <-->|"bug & pesan"| D8
    P6 <-->|"transaksi"| D7
    P6 -->|"catat perubahan"| D9

    %% Profil publik
    D5 -->|"visi, misi, layanan, kontak"| P3
    P3 -->|"profil perusahaan di beranda"| PENGUNJUNG
```

---

## 3. DFD Level 2 — Proses 2.0 Konsultasi AI

Proses paling kompleks karena melibatkan kuota, redaksi PII, retrieval, dan
penanganan kegagalan layanan AI.

```mermaid
flowchart TB
    PENGGUNA["Pengguna"]
    LLM["Penyedia LLM"]

    P21(("2.1<br/>Validasi &<br/>Cek Kuota"))
    P22(("2.2<br/>Redaksi PII"))
    P23(("2.3<br/>Retrieval<br/>Regulasi"))
    P24(("2.4<br/>Panggil<br/>LLM"))
    P25(("2.5<br/>Simpan Jawaban<br/>& Sumber"))
    P26(("2.6<br/>Umpan Balik<br/>Jawaban"))

    D1[("D1 users<br/>(quota_date, quota_used)")]
    D3[("D3 chat_sessions /<br/>chat_messages")]
    D6[("D6 answer_ratings /<br/>answer_reports")]
    D10[("D10 ChromaDB +<br/>kb.sqlite3")]
    METRIK[("D11 metrics log")]

    PENGGUNA -->|"pertanyaan"| P21
    P21 <-->|"baca & potong kuota"| D1
    P21 -->|"pertanyaan valid"| P22
    P22 -->|"pertanyaan + riwayat teredaksi"| P23
    P23 <-->|"vektor + kata kunci"| D10
    P23 -->|"potongan konteks"| P24
    P24 -->|"prompt + konteks"| LLM
    LLM -->|"jawaban"| P24
    P24 -->|"durasi, status, jumlah sumber"| METRIK
    P24 -->|"jawaban / fallback degraded"| P25
    P25 -->|"simpan pesan user & asisten"| D3
    P25 -->|"jawaban + sumber + disclaimer"| PENGGUNA

    PENGGUNA -->|"rating 1-5 / alasan laporan"| P26
    P26 -->|"simpan rating / laporan"| D6

    P25 -.->|"bila layanan AI gagal:<br/>kuota dikembalikan"| D1
```

Aturan penting pada Level 2:

| Kondisi | Perilaku |
|---|---|
| Kuota habis | Pertanyaan ditolak, tidak ada panggilan LLM |
| Layanan AI gagal/timeout | Jawaban `degraded`, `quota_used` dikembalikan |
| PII terdeteksi | Diganti `[email]`, `[telepon]`, `[nik]`, `[npwp]` pada salinan yang dikirim |
| Riwayat panjang | Hanya 4 pesan terakhir, maksimal 400 karakter per pesan |

---

## 4. DFD Level 2 — Proses 4.0 Premium & Pembayaran

```mermaid
flowchart TB
    PENGGUNA["Pengguna"]
    MIDTRANS["Midtrans"]
    ADMIN["Admin"]

    P41(("4.1<br/>Tampilkan<br/>Paket"))
    P42(("4.2<br/>Buat<br/>Transaksi"))
    P43(("4.3<br/>Sinkronisasi<br/>Status"))
    P44(("4.4<br/>Aktivasi<br/>Premium"))
    P45(("4.5<br/>Monitoring<br/>Pembayaran"))

    D1[("D1 users")]
    D7[("D7 transactions")]
    D9[("D9 audit_logs")]

    PENGGUNA -->|"buka /premium"| P41
    P41 <-->|"plan, premium_until, kuota"| D1
    P41 -->|"harga, masa aktif, riwayat"| PENGGUNA

    PENGGUNA -->|"klik bayar"| P42
    P42 -->|"baris transaksi pending"| D7
    P42 -->|"POST Snap /transactions"| MIDTRANS
    MIDTRANS -->|"token + redirect URL"| P42
    P42 -->|"redirect ke halaman bayar"| PENGGUNA

    MIDTRANS -->|"webhook /midtrans/notification"| P43
    PENGGUNA -->|"kembali ke /premium/selesai"| P43
    P43 -->|"GET status order"| MIDTRANS
    MIDTRANS -->|"transaction_status"| P43
    P43 <-->|"update status, paid_at"| D7
    P43 -->|"bila lunas"| P44

    P44 -->|"plan=premium,<br/>premium_until + N hari"| D1
    P44 -->|"catat transaction.paid"| D9

    ADMIN -->|"buka /admin/pembayaran"| P45
    P45 <-->|"daftar & ringkasan transaksi"| D7
    P45 -->|"pendapatan, status, pembeli"| ADMIN
```

Aturan idempotensi: premium hanya diaktifkan saat transaksi berubah dari
belum-lunas menjadi lunas. Webhook berulang tidak menambah masa aktif lagi.

---

## 5. Kamus Data

### 5.1 Entitas luar

| Entitas | Peran |
|---|---|
| Pengunjung | Belum login; dapat membaca kebijakan, konsultasi (mode tamu), mendaftar, dan mengirim laporan bug/pesan |
| Pengguna | Akun terverifikasi; konsultasi, riwayat, premium, pesan admin |
| Admin | Akun `role=admin` dengan OTP; mengelola konten dan menindaklanjuti laporan |
| Midtrans | Payment gateway (Snap sandbox/produksi) |
| Penyedia LLM | Endpoint kompatibel OpenAI yang dipanggil layanan RAG |

### 5.2 Data store

| Kode | Tabel / media | Isi | Pemilik proses |
|---|---|---|---|
| D1 | `users` | Akun, role, plan, premium_until, kuota harian, two_factor_at | 1.0, 2.0, 4.0, 6.0 |
| D2 | `business_profiles` | Nama usaha, jenis, kota | 1.0 |
| D3 | `chat_sessions`, `chat_messages` | Sesi konsultasi (akun/tamu), pesan, sumber, flag degraded | 2.0 |
| D4 | `regulations` | Metadata kebijakan, berkas PDF, `rag_doc_id` | 3.0, 6.0 |
| D5 | `company_profiles` | Visi, misi, layanan, kontak publik | 3.0, 6.0 |
| D6 | `answer_ratings`, `answer_reports` | Nilai 1–5, alasan laporan, status tindak lanjut (akun/tamu) | 2.0, 6.0 |
| D7 | `transactions` | Order Midtrans, nominal, status, `paid_at`, respons mentah | 4.0, 6.0 |
| D8 | `bug_reports`, `contact_messages` | Laporan bug + screenshot, pesan pengguna | 5.0, 6.0 |
| D9 | `audit_logs`, `otp_codes` | Jejak perubahan konten, OTP admin (hash) | 1.0, 4.0, 6.0 |
| D10 | ChromaDB + `kb.sqlite3` | Indeks vektor & kata kunci dokumen kebijakan | 2.0, 3.0, 6.0 |
| D11 | `storage/logs/metrics-*.log` | Metrik JSON: status, durasi, jumlah sumber | 2.0 |

### 5.3 Arus data kunci

| Arus | Dari → Ke | Muatan |
|---|---|---|
| Pertanyaan konsultasi | Pengguna → 2.1 | Teks 3–1000 karakter |
| Prompt LLM | 2.4 → LLM | Pertanyaan teredaksi + maks. 4 pesan riwayat |
| Jawaban AI | 2.4 → 2.5 | Teks jawaban, daftar sumber, flag degraded |
| Notifikasi Midtrans | Midtrans → 4.3 | `order_id`, `transaction_status`, `gross_amount`, `signature_key` |
| Aktivasi premium | 4.4 → D1 | `plan=premium`, `premium_until` baru |
| Audit perubahan | 6.0 → D9 | `actor_id`, `action`, `subject`, metadata |

---

## 6. Batas Kepercayaan dan Kontrol

| Batas | Kontrol |
|---|---|
| Publik → aplikasi | CSRF token, validasi request, throttle form bantuan (5/menit) |
| Pengguna → area admin | Middleware `auth`, `verified`, `admin`; OTP wajib segar (30 menit) |
| Laravel → FastAPI | `Authorization: Bearer <RAG_TOKEN>`; FastAPI menolak 401 tanpa token |
| FastAPI → LLM | Hanya teks pertanyaan teredaksi; tanpa nama/email/profil usaha |
| Midtrans → aplikasi | Signature SHA-512 diverifikasi; order tak dikenal diabaikan |
| Aplikasi → publik | Security headers + CSP nonce; PDF & screenshot lewat disk `public` |
