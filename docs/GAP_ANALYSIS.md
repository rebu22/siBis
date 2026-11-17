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
| RF-07 | Berlangganan Premium | [ ] | Kolom `plan`/`premium_until` ada, tapi **tanpa payment gateway & UI**. Tombol "Segera hadir" |
| RF-08 | Laporkan bug | [x] | `/lapor-bug` (publik) + screenshot di disk `public/lapor-bug` |
| RF-09 | Kirim pesan ke admin | [x] | `/hubungi-admin` (wajib login) + `ContactMessage` |
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
| RF-21 | Monitoring pembayaran | [ ] | Belum ada tabel transaksi & UI |
| RF-22 | Hapus akun + data | [x] | `ProfileController@destroy`, cascade ke sesi chat |
| RF-23 | Lihat riwayat konsultasi | [x] | `ConsultationController@history` / `show` |
| RF-24 | Hapus riwayat konsultasi | [x] | `history.destroy` + `history.destroyAll`, tombol di daftar & detail riwayat |
| RF-25 | Nilai jawaban AI | [x] | `ConsultationController@rate` |
| RF-26 | Laporkan jawaban AI | [x] | User melapor; admin meninjau & mengubah status di `/admin/laporan` |
| RF-27 | Hapus dokumen hukum | [x] | `destroyRegulation` + hapus dari RAG; SRS minta berkas **diarsipkan**, kode menghapus file |

**Ringkasan: 25 selesai, 2 belum.**

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
| UC-12 | Berlangganan premium | [ ] |
| UC-13 | Monitoring pembayaran | [ ] |
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
| NFR-16 | Anonimisasi ke LLM | [~] | Hanya teks pertanyaan yang dikirim; belum ada penyaring PII |
| NFR-17 | Fallback saat LLM mati | [x] | `degraded` answer, kuota tidak dipotong |
| NFR-18 | Log request/response LLM | [ ] | Hanya log kegagalan |
| NFR-19 | Log latensi & status per request | [ ] | Belum ada |
| NFR-20 | Metrik & alerting | [ ] | Belum ada |
| NFR-21 | Audit log perubahan konten | [x] | `AuditLog::record()` dipanggil di semua aksi tulis admin; halaman `/admin/audit` |
| NFR-23 | Token internal Laravel↔FastAPI | [x] | `RAG_TOKEN`, `_auth()` |
| NFR-24 | Backup harian, retensi 30 hari | [ ] | Belum ada jadwal/konfigurasi |
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

- `README.md` masih menyebut `/artikel`, halaman artikel, dan `PANDUAN_PRESENTASI.txt`
  (file tidak ada). Juga menulis "unggah PDF belum dibangun" padahal sudah ada.
- `ROADMAP.md` merujuk `docs/DFD.md` yang tidak ada (`docs/` kosong).
- Struktur folder belum dipisah `frontend/` + `backend/` + `ai-service/`
  (rencana ini ditunda sampai fitur selesai).

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

### Fase C — Premium
- [ ] RF-07 / UC-12: pilih payment gateway, tabel `transactions`, halaman paket
- [ ] RF-21 / UC-13: monitoring transaksi & status langganan di admin
- [ ] Aktivasi `premium_until` otomatis dari webhook pembayaran

### Fase D — Operasional
- [ ] NFR-24: backup harian + retensi 30 hari
- [ ] NFR-18/19/20: logging latensi, metrik, alerting
- [ ] NFR-16: penyaring PII sebelum kirim ke LLM

### Fase E — Rapikan
- [ ] Update README, ROADMAP, buat DFD
- [ ] Pisah folder `frontend/`, `backend/`, `ai-service/`
- [ ] Tulis ulang SRS sesuai implementasi final
