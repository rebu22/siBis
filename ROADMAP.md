# ROADMAP siBis

Status: **fitur selesai**, tinggal penyesuaian deployment. Dokumen ini mencatat
urutan pengerjaan yang sudah dilalui, bukan rencana kosong.

## Fase 0 — Fondasi
- [x] SRS lama dibaca (RF-01..27, UC-01..25, NFR-01..25)
- [x] Audit gap SRS vs kode (`docs/GAP_ANALYSIS.md`)
- [x] README, DFD, dan panduan operasional

## Fase 1 — Kontrak data
- [x] Skema: users, chat_sessions, chat_messages, regulations, transactions,
      company_profiles, bug_reports, contact_messages, answer_ratings,
      answer_reports, audit_logs, otp_codes
- [x] Indeks RAG: ChromaDB (vektor) + `kb.sqlite3` (kata kunci)
- [x] Kontrak Laravel ↔ FastAPI: `/rag/ask`, `/rag/ingest/pdf`,
      `/rag/documents`, token internal
- [x] Kuota Free 5/hari, Premium 100/hari, masa aktif `premium_until`

## Fase 2 — Scaffolding
- [x] Laravel + Inertia + Vue + Tailwind
- [x] Auth session, verifikasi email, reset password, 2FA OTP admin
- [x] Halaman publik: beranda, kebijakan PDF, detail
- [x] FastAPI: ingest PDF, retrieval, LLM, fallback saat layanan mati

## Fase 3 — Fitur inti
- [x] Auth: register, login, logout, reset, hapus akun
- [x] Konsultasi AI, riwayat, hapus riwayat, rating, lapor jawaban
- [x] Premium: Midtrans Snap, webhook, aktivasi otomatis, monitoring admin
- [x] CMS: unggah/ubah/hapus kebijakan PDF, edit profil perusahaan
- [x] Publik: cari kebijakan, detail PDF
- [x] Bantuan: lapor bug + screenshot, pesan ke admin, kotak masuk admin

## Fase 4 — Non-fungsional
- [x] Password hash, HTTPS/HSTS saat secure, security headers + CSP nonce
- [x] Token internal Laravel ↔ FastAPI
- [x] Anti-halusinasi: prompt wajib merujuk konteks + sitasi
- [x] Fallback saat LLM mati tanpa memotong kuota
- [x] Redaksi PII (email, telepon, NIK, NPWP) sebelum kirim ke LLM
- [x] Audit log perubahan konten admin
- [x] Metrik latensi/status LLM, health check, alerting ke log
- [x] Backup harian + retensi 30 hari
- [x] Aksesibilitas dasar: kontras, fokus keyboard, label

## Fase 5 — Rapikan & SRS baru
- [x] Pisah folder `backend/`, `frontend/`, `ai-service/`
- [x] README, ROADMAP, DFD diperbarui
- [x] SRS baru sesuai implementasi (`docs/SRS_siBis.md`)

## Sisa pekerjaan deployment (bukan fitur)
- [ ] Isi kredensial Midtrans produksi dan URL webhook
- [ ] Pasang HTTPS di reverse proxy (NFR-10)
- [ ] Ukur latensi nyata di hosting (NFR-07/08)
- [ ] Audit WCAG 2.1 AA penuh (NFR-25)
- [ ] Evaluasi kualitas jawaban AI berkala (NFR-12)
- [ ] Salin backup ke luar server (S3/rsync)
