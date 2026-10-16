# DFD siBis — dari SRS (tanpa SDD)

Diturunkan teliti dari RF-01..21 + UC-01..24. SDD contoh (SATSET) tidak dipakai.

## DFD Level 0 — Diagram Konteks

Entitas luar: **Pelaku Usaha (User)**, **Admin**, **Payment Gateway**, **LLM API**, **Layanan Email**.
Sistem: **siBis**.

```mermaid
flowchart LR
  U[Pelaku Usaha] -->|register/login, pertanyaan, rating, laporan, langganan, bug/pesan| S((siBis))
  S -->|halaman profil/artikel, jawaban AI + referensi + disclaimer, bukti bayar, status| U
  A[Admin] -->|2FA login, PDF regulasi + metadata, artikel, konten profil, status bug/pesan| S
  S -->|dashboard, daftar transaksi/laporan/inbox| A
  S -->|transaksi + callback| PG[Payment Gateway]
  PG -->|notifikasi status| S
  S -->|prompt anonim + konteks| LLM[LLM API]
  LLM -->|jawaban| S
  S -->|link verifikasi, OTP, reset| MAIL[Email]
```

Aliran kunci: User wajib login untuk chat/premium/bug/pesan (UC-07/12/21/22). Admin wajib 2FA OTP (UC-03). Data ke LLM anonim (NFR-16).

## DFD Level 1 — Proses utama

Data store: **D1 MySQL** (akun, chat, artikel, transaksi, profil, bug/pesan), **D2 ChromaDB** (embedding hukum).

```mermaid
flowchart TB
  U[Pelaku Usaha] --> P1[1. Kelola Akun]
  P1 <--> D1[(D1 MySQL)]
  P1 --> MAIL[Email: verifikasi/OTP/reset]
  U --> P2[2. Konsultasi AI RAG]
  P2 <--> D1
  P2 <--> D2[(D2 ChromaDB)]
  P2 <--> LLM[LLM API]
  U --> P4[4. Langganan & Bayar]
  P4 <--> D1
  P4 <--> PG[Payment Gateway]
  P2 --> P4
  U --> P5[5. Bantuan: rating/lapor/bug/pesan]
  P5 <--> D1
  A[Admin] --> P3[3. Kelola Konten & KB]
  P3 <--> D1
  P3 --> D2
  P3 --> P2
  A --> P6[6. Monitor: transaksi/bug/pesan]
  P6 <--> D1
  P1 --> P2
```

- P1 = UC-01/02/03/04/05/06 (RF-10..13). Lock brute-force, OTP admin, cabut sesi lama, hapus data UU PDP.
- P2 = UC-07/08/09/10/11 (RF-05/06). Detail di Level 2.
- P3 = UC-14/15/16/17/18 (RF-14..18). Upload PDF → ekstrak → chunk → indeks D2. CRUD artikel + edit profil CMS.
- P4 = UC-12/13 (RF-07/21). Buat transaksi → gateway → notifikasi → aktif kuota. Admin sinkron ulang.
- P5 = UC-10/11/21/22 + UC-19/20 publik (RF-01..04/08/09).
- P6 = UC-13/23/24 (RF-19/20/21). Status bug Baru/Proses/Selesai, pesan baca/belum.

## DFD Level 2 — P2 Konsultasi AI (inti)

```mermaid
flowchart LR
  U[User] --> A[2.1 Validasi + cek kuota<br/>D1: user & kuota]
  A -->|kuota habis| PREM[Tawarkan Premium P4]
  A -->|valid| B[2.2 Retrieval<br/>FastAPI -> D2 ChromaDB]
  B -->|konteks regulasi| C[2.3 Generate<br/>prompt anonim -> LLM API]
  C -->|gagal| F[Fallback: 'Layanan konsultasi sementara tidak tersedia'<br/>tanpa potong kuota]
  C -->|ada| D[2.4 Saji + simpan<br/>jawaban + blok referensi + disclaimer -> D1, kurangi kuota]
  D --> U2[User terima jawaban]
  B -->|tanpa rujukan| E[Nyatakan keterbatasan + sarankan advokat]
```

Aturan dari SRS: kuota dicek dulu (UC-07). Gagal AI = coba lagi tanpa potong kuota. Tanpa rujukan KB = jangan mengarang, tampilkan keterbatasan. Tiap jawaban: ringkasan awam + dasar hukum (nama+nomor+tahun) + disclaimer edukasi. Log req/res + latensi tanpa data sensitif (NFR-18/19).

## Traceability singkat
RF-01→P5/P3, RF-02/03/04→P5, RF-05/06→P2, RF-07→P4, RF-08→P5/P6, RF-09→P5/P6, RF-10..13→P1, RF-14/15/16→P3, RF-17→P3, RF-18→P3, RF-19/20→P6, RF-21→P4/P6.
