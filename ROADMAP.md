# ROADMAP siBis (dari SRS saja)

Saran: **bertahap**. Jangan langsung code penuh. Alasan: RAG + payment + 2FA rawan rework kalau DB/kontrak API belum fix.
Format: pakai `.md` ini, bukan `.txt` — render rapi di GitHub, bisa checklist, jadi acuan commit.

## Fase 0 — Fondasi (sekarang)
- [x] SRS dibaca (55 hal, RF-01..21, UC-01..24, NFR-01..22)
- [x] README disesuaikan konteks hukum AI
- [x] DFD Level 0-1-2 dari SRS (`docs/DFD.md`)
- [ ] SDD siBis asli (kalau ada, taruh di sini). Contoh SATSET sudah dihapus biar tidak bingung.

## Fase 1 — Kontrak data (sebelum code)
- [ ] Skema MySQL: users, chat_history, articles, transactions, profiles, bug_reports, messages, ratings
- [ ] Skema ChromaDB: collection dokumen_hukum (chunk + metadata judul/nomor/tahun/kategori)
- [ ] Kontrak Laravel<->FastAPI: `POST /rag/ask`, `POST /rag/ingest`, format JSON + auth internal
- [ ] Aturan kuota Free vs Premium + status langganan

## Fase 2 — Scaffolding
- [ ] Laravel + Inertia + Vue skeleton, auth session + Sanctum, 2FA OTP admin
- [ ] Halaman publik: home profil singkat + chatbot kanan-bawah, artikel list/search/detail
- [ ] FastAPI skeleton: ingest PDF, retrieval, panggil LLM API, fallback `Layanan konsultasi sementara tidak tersedia`

## Fase 3 — Fitur inti (urut UC)
1. UC-01/02/04/05/06 auth (register verifikasi, login lock, logout, reset, hapus akun)
2. UC-07/08/09/10/11 chat AI + history + rating + lapor jawaban
3. UC-12/13 premium + monitoring pembayaran
4. UC-14..18 admin CMS (upload PDF KB, CRUD artikel, edit profil)
5. UC-19/20 publik + UC-21..25 bug/pesan + hapus dokumen hukum

## Fase 4 — NFR / QA
- Home ≤5 dtk, chat ≤10 dtk, 24x7, responsif 360px, Chrome/FF/Edge/Safari
- Bcrypt/Argon2, HTTPS, UU PDP, anonim ke LLM, anti-halusinasi traceable, logging tanpa data sensitif

Tiap fase = 1+ commit `fase: ...`. Update checklist ini tiap push.
