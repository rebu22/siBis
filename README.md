# siBis — Sistem Informasi Konsultasi Hukum Bisnis Berbasis AI

> Sumber kebenaran: `SRS_11241029.pdf` (55 hal, 8 Okt 2026, Fazly Pasya Pahlevi 11241029).
> README ini disesuaikan dari SRS. Kalau ada konflik, SRS menang.

## 1. Konteks Web Ini (dari SRS §1-2)
Website siBis = **company profile + chatbot konsultasi hukum bisnis awal** untuk pelaku usaha di Indonesia.
Topik: perpajakan, perizinan berusaha, pendirian badan usaha. Setiap jawaban AI wajib ada **referensi regulasi resmi + disclaimer bukan pengganti advokat**.

Bukan marketplace, bukan inventaris, bukan transaksi daring. User dasar: register/login/reset saja. Chat: lihat + hapus saja, tanpa search/arsip canggih.

## 2. Stack Wajib (SRS §3.2)
- Front-End: **Vue.js**
- Back-End Web & CMS: **PHP Laravel** via **Inertia.js** (bukan REST terpisah, session-based + Sanctum)
- Back-End AI: **Python FastAPI** — pipeline RAG: ingest PDF → chunk → ChromaDB → LLM API
- DB: **MySQL** (akun, chat, transaksi, profil) + **ChromaDB** (embedding dokumen hukum)
- Lain: Payment Gateway (QRIS/VA) untuk Premium, LLM API eksternal, HTTPS/TLS, hash Bcrypt/Argon2

Komunikasi: Vue --Inertia--> Laravel --REST internal--> FastAPI --> Vector DB + LLM.

## 3. Fitur Wajib (RF-01 s/d RF-21 / UC-01 s/d UC-24)
Publik: lihat company profile (visi/misi/layanan/kontak), lihat/cari/detail artikel hukum.
User login: register + verifikasi email, login (lock brute-force), logout, reset password link berbatas waktu, hapus akun + data (UU PDP), tanya AI, lihat/hapus history chat, rating/feedback jawaban, lapor jawaban tidak akurat, langganan premium via gateway, lapor bug (deskripsi+email+screenshot), kirim pesan ke admin (wajib login).
Admin (2FA OTP): monitoring pembayaran + sinkron ulang, upload PDF regulasi + metadata (judul/nomor/tahun/kategori) untuk knowledge base, CRUD artikel (draf/terbit), edit konten profil tanpa kode, terima bug (Baru/Proses/Selesai) + pesan user (baca/belum, balas via email).

Alur inti UC-07: validasi input + cek kuota → FastAPI + retrieval ChromaDB → LLM susun jawaban + dasar hukum → tampil + disclaimer → simpan riwayat, kurangi kuota. Kuota habis → tawarkan premium. AI gagal → coba lagi tanpa potong kuota. Tidak ada rujukan → nyatakan keterbatasan + sarankan ahli.

## 4. Batasan Scope (dilarang nambah di luar ini tanpa revisi SRS)
1. Hanya web, tanpa aplikasi mobile khusus.
2. Bukan layanan hukum profesional / pengganti advokat. Disclaimer eksplisit di UI chatbot.
3. AI hanya jawab dari dokumen di knowledge base. Di luar itu: tolak + sarankan konsultan berlisensi.
4. Upload file hanya PDF admin untuk KB. Tidak ada upload bebas user.
5. Butuh internet stabil untuk chatbot.

## 5. NFR Kunci (diuji QA)
- Load home ≤5 dtk, chat respon ≤10 dtk (termasuk RAG). 24x7, availability ≥99.5%.
- Crash backend ≤0.1%/bln, error/timeout AI ≤1%/bln, gangguan LLM API ≤0.5%/bln.
- UI konsisten + responsif (min 360px), Chrome/Firefox/Edge/Safari tanpa error.
- Security: hash Bcrypt/Argon2, HTTPS/TLS, chat = data pribadi UU PDP No.27/2022, anonimkan data ke LLM (tanpa nama/email).
- Accuracy: anti-halusinasi, tiap jawaban traceable ke sumber. Fallback: `Layanan konsultasi sementara tidak tersedia` tanpa crash.
- Logging: log LLM req/res + latensi ChromaDB tanpa data sensitif, audit tiap ubah artikel/konten, alert metrik.

## 6. Anti-Slop Guide untuk siBis Hukum
Prinsip: spesifik > generik. Tiap klaim ditempel regulasi/nomor/tahun.

KATA TERLARANG: `delve, unlock, elevate, seamless, cutting-edge, revolutionize, game-changer, state-of-the-art, in today's fast-paced world, tailored solution, robust solution`.

POLA TERLARANG:
- Hero `Selamat datang di masa depan hukum / Revolusikan bisnis Anda`
- 3 kartu `Efisien / Terpercaya / Inovatif` tanpa angka/pasal
- Jawaban AI tanpa sumber: `Menurut hukum yang berlaku...` tanpa sebut UU/PP + tahun
- CTA generik `Get Started / Learn More` doang
- Gradient ungu-biru + ilustrasi robot 3D + semua section centered

WAJIB siBis (dari SRS §3.1):
- Warna: **biru tua + putih** profesional terpercaya (sesuai SRS, bukan hijau warung).
- Layout: home = profil singkat perusahaan + **ikon chatbot kanan-bawah**. Navigasi jelas konsisten.
- Chatbot: area input + kirim + riwayat dinamis, **tiap jawaban ada blok referensi sumber hukum di bawah**.
- Copy: maks 15 kata/kalimat, tombol spesifik (`Tanya Izin Usaha`, `Lihat Artikel Pajak`, bukan `Mulai`).
- Legal safety: tiap jawaban AI = ringkasan awam + dasar hukum (nama dokumen/nomor/tahun) + `Informasi edukasi awal, bukan nasihat mengikat. Konsultasikan ke advokat berlisensi.`

Contoh:
❌ Slop: `Rasakan kemudahan seamless konsultasi hukum cutting-edge untuk bisnis Anda.`
✅ Fix: `Tanya syarat PT Perorangan. Jawaban dari PP 8/2021 + link sumber, gratis 5x/hari.`

## 7. Prompt AI yang Boleh (tempel tiap generate)
```
Konteks: siBis, konsultasi awal hukum bisnis Indonesia (pajak, izin, PT). Jawaban hanya dari knowledge base.
Dilarang: delve, unlock, seamless, revolutionize, kata generik tanpa sumber.
Wajib: sebut dasar hukum (nama + nomor + tahun), 1 disclaimer edukasi, maks 15 kata/kalimat.
Jika tidak ada di KB: tulis [tidak ada di knowledge base], jangan mengarang pasal, sarankan advokat.
Bahasa: Indonesia formal santai, mudah dipahami pelaku usaha.
```

## 8. Checklist Sebelum Push
- [ ] Bisa tebak ini siBis hukum tanpa lihat logo? Ada blok referensi + disclaimer?
- [ ] Ada 1 fakta hukum (UU/PP + tahun) atau screenshot asli di atas fold?
- [ ] Tidak ada kata/pola terlarang di atas?
- [ ] Warna biru tua-putih, chatbot kanan-bawah, bukan template ungu generik?
- [ ] Home ≤5 dtk, chat ≤10 dtk, responsif 360px?
- [ ] 5 detik test: user paham ini konsultasi awal + ada batas Free/Premium?

---
Aturan main: lebih baik khas + patuh SRS, daripada rapi tapi halusinasi dan mirip 1000 web AI.
