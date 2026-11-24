# Gap Analysis siBis — SRS vs Implementasi

Diperbarui: 17 November 2026

Dokumen ini membandingkan SRS (`SRS_11241029.pdf`) dengan kode yang benar-benar ada.
Dipakai sebagai checklist sampai aplikasi selesai, **sebelum SRS ditulis ulang**.

Status: `[x]` selesai · `[~]` sebagian · `[ ]` belum ada

---

## 1. Kebutuhan fungsional (RF)

| RF | Fitur | Status | Bukti / Catatan |
|---|---|---|---|
| RF-01 | Company profile | [x] | `CompanyProfile` + shared prop `company`, dipakai `Home.vue` |
| RF-02 | Daftar artikel/berita | [x] | Berubah menjadi daftar **kebijakan PDF** (`RegulationController@index`) |
| RF-03 | Pencarian artikel | [x] | `Regulation::scopeSearch` + filter kategori |
| RF-04 | Detail artikel | [x] | `RegulationController@show` |
| RF-05 | Bertanya ke AI | [x] | `ConsultationController@ask` |
| RF-06 | Proses RAG | [x] | `RagClient` → FastAPI `/rag/ask`, sumber disimpan di `chat_messages.sources` |
| RF-07 | Berlangganan Premium | [x] | `/premium` + Midtrans Snap (sandbox default), aktivasi otomatis via webhook/status API |
| RF-08 | Laporkan bug | [x] | `/lapor-bug` (publik) + screenshot di disk `public/lapor-bug` |
| RF-09 | Kirim pesan ke admin | [x] | `/hubungi-admin` (publik; tamu isi nama+email) + `ContactMessage` |
| RF-10 | Login + anti brute-force | [x] | `LoginRequest` + `RateLimiter` (5 percobaan) |
| RF-11 | Logout | [x] | `AuthenticatedSessionController@destroy` |
| RF-12 | Register + verifikasi email | [x] | `RegisteredUserController`, `MustVerifyEmail` |
| RF-13 | Reset password | [x] | Route Breeze + `PasswordResetLinkController` |
| RF-14 | Buat artikel | [x] | Diganti "unggah kebijakan PDF" (`AdminController@storeRegulation`) |
| RF-15 | Edit artikel | [x] | `AdminController@updateRegulation` |
| RF-16 | Hapus artikel | [x] | `AdminController@destroyRegulation` |
| RF-17 | Edit konten profil (CMS) | [x] | `AdminController@editProfile`/`updateProfile` + halaman `Admin/Profile.vue` |
| RF-18 | Unggah dokumen hukum | [x] | PDF + `RagClient::ingestPdf`; batas kode **20 MB**, SRS 10 MB |
| RF-19 | Admin terima laporan bug | [x] | `/admin/laporan-bug`: filter status, catatan admin, audit log |
| RF-20 | Admin terima pesan user | [x] | `/admin/pesan`: kotak masuk, filter, tandai dibaca/belum |
| RF-21 | Monitoring pembayaran | [x] | `/admin/pembayaran`: pendapatan lunas, transaksi tertunda, filter status |
| RF-22 | Hapus akun + data | [x] | `ProfileController@destroy`, cascade ke sesi chat |
| RF-23 | Lihat riwayat konsultasi | [x] | `ConsultationController@history` / `show` |
| RF-24 | Hapus riwayat konsultasi | [x] | `history.destroy` + `history.destroyAll`, tombol di daftar & detail riwayat |
| RF-25 | Nilai jawaban AI | [x] | `ConsultationController@rate` |
| RF-26 | Laporkan jawaban AI | [x] | User melapor; admin meninjau & mengubah status di `/admin/laporan` |
| RF-27 | Hapus dokumen hukum | [x] | `destroyRegulation` + hapus dari RAG; SRS minta berkas **diarsipkan**, kode menghapus file |

**Ringkasan: 27 selesai, 0 belum.**

---

## 2. Use case (UC)

| UC | Nama | Status |
|---|---|---|
| UC-01 | Register | [x] |
| UC-02 | Login | [x] |
| UC-03 | Login admin (2FA) | [x] |
| UC-04 | Logout | [x] |
| UC-05 | Reset password | [x] |
| UC-06 | Hapus akun | [x] |
| UC-07 | Bertanya ke AI | [x] |
| UC-08 | Lihat riwayat | [x] |
| UC-09 | Hapus riwayat | [x] |
| UC-10 | Rating jawaban | [x] |
| UC-11 | Lapor jawaban AI | [x] (user) |
| UC-12 | Berlangganan premium | [x] |
| UC-13 | Monitoring pembayaran | [x] |
| UC-14 | Unggah dokumen hukum | [x] |
| UC-15 | Buat artikel | [x] (bentuk PDF) |
| UC-16 | Edit artikel | [x] (bentuk PDF) |
| UC-17 | Hapus artikel | [x] (bentuk PDF) |
| UC-18 | Edit konten profil | [x] |
| UC-19 | Lihat company profile | [x] |
| UC-20 | Lihat & cari artikel | [x] (kebijakan PDF) |
| UC-21 | Laporkan bug | [x] |
| UC-22 | Kirim pesan ke admin | [x] |
| UC-23 | Admin terima laporan bug | [x] |
| UC-24 | Admin terima pesan user | [x] |
| UC-25 | Hapus dokumen hukum | [x] |

---

## 3. Kebutuhan non-fungsional (NFR)

| NFR | Kebutuhan | Status | Catatan |
|---|---|---|---|
| NFR-09 | Hash password Bcrypt/Argon2 | [x] | `'password' => 'hashed'` |
| NFR-10 | HTTPS/TLS | [~] | HSTS hanya saat `isSecure()`; tergantung deployment |
| NFR-12 | Anti-halusinasi + sumber | [~] | Prompt + sitasi; belum ada evaluasi otomatis |
| NFR-16 | Anonimisasi ke LLM | [x] | `PiiFilter` meredaksi email/telepon/NIK/NPWP pada pertanyaan & riwayat; pesan asli tetap di DB |
| NFR-17 | Fallback saat LLM mati | [x] | `degraded` answer, kuota tidak dipotong |
| NFR-18 | Log request/response LLM | [x] | Channel `metrics`: status, durasi, jumlah sumber, panjang jawaban — tanpa isi teks |
| NFR-19 | Log latensi & status per request | [x] | `RagClient` mencatat `duration_ms` + status per panggilan |
| NFR-20 | Metrik & alerting | [x] | `sibis:health` tiap 5 menit (log `critical`); RAG lambat memicu warning |
| NFR-21 | Audit log perubahan konten | [x] | `AuditLog::record()` dipanggil di semua aksi tulis admin; halaman `/admin/audit` |
| NFR-23 | Token internal Laravel↔FastAPI | [x] | `RAG_TOKEN`, `_auth()` |
| NFR-24 | Backup harian, retensi 30 hari | [x] | `sibis:backup` harian 02:30, disk `backups`, prune otomatis |
| NFR-25 | WCAG 2.1 AA | [~] | Ada `ContrastTest`, fokus keyboard, label; belum diaudit penuh |
| NFR-07/08 | Home ≤5 dtk, chat ≤10 dtk | [~] | Tergantung model LLM & hosting; belum diukur |

---

## 4. Ketidaksesuaian SRS vs kode

1. **Artikel teks → kebijakan PDF.** SRS RF-14..16/UC-15..17/UC-20 menyebut artikel/berita
   dengan `body` teks. Kode sudah pindah ke regulasi PDF (migrasi
   `2026_11_04_000003_regulations_from_articles.php`). Perlu keputusan: pertahankan PDF,
   atau kembalikan artikel teks, atau dukung keduanya.
2. **Batas unggah.** SRS 10 MB, validasi kode 20 MB.
3. **Hapus dokumen.** SRS RF-27: berkas asli diarsipkan. Kode: `Storage::delete()`.
4. **Nomor UC di komentar kode** kadang bergeser dari SRS (mis. upload PDF ditulis `UC-18`,
   padahal SRS `UC-14`).

---

## 5. Dokumentasi yang sudah usang

Dokumen lama sudah diperbarui pada Fase E:

- `README.md` ditulis ulang: struktur `backend/` + `frontend/` + `ai-service/`,
  alur pengguna/admin, Midtrans, operasional, dan pengujian.
- `ROADMAP.md` ditulis ulang: semua fase ditandai selesai; sisa hanya item
  deployment.
- `docs/DFD.md` dibuat: DFD Level 0–2 + kamus data.
- Folder sudah dipisah menjadi `backend/`, `frontend/`, dan `ai-service/`.

Yang sengaja dibiarkan sebagai arsip: `SRS_11241029.pdf` di akar repo
(dokumen asli yang digantikan). SRS pengganti ada di `docs/SRS_siBis.md`.

---

## 6. Usulan urutan pengerjaan

### Fase A — Privasi & CMS inti (paling cepat, tanpa dependensi luar) — SELESAI
- [x] RF-24 / UC-09: hapus satu sesi + hapus semua riwayat
- [x] RF-17 / UC-18: halaman admin edit company profile
- [x] RF-26 admin: ubah status & catatan laporan jawaban
- [x] NFR-21: panggil `AuditLog::record()` di semua aksi tulis admin

### Fase B — Kanal komunikasi — SELESAI
- [x] RF-08 / UC-21: form lapor bug + unggah screenshot
- [x] RF-19 / UC-23: halaman admin laporan bug (status Baru/Proses/Selesai)
- [x] RF-09 / UC-22: form pesan ke admin
- [x] RF-20 / UC-24: kotak masuk admin + tandai dibaca

### Fase C — Premium — SELESAI
- [x] RF-07 / UC-12: pilih payment gateway, tabel `transactions`, halaman paket
- [x] RF-21 / UC-13: monitoring transaksi & status langganan di admin
- [x] Aktivasi `premium_until` otomatis dari webhook pembayaran

### Fase D — Operasional — SELESAI
- [x] NFR-24: backup harian + retensi 30 hari
- [x] NFR-18/19/20: logging latensi, metrik, alerting
- [x] NFR-16: penyaring PII sebelum kirim ke LLM

### Fase E — Rapikan — SELESAI
- [x] Update README, ROADMAP, buat DFD
- [x] Pisah folder `frontend/`, `backend/`, `ai-service/`
- [x] Tulis ulang SRS sesuai implementasi final
