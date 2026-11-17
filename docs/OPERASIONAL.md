# Operasional siBis

Panduan singkat untuk NFR-16, NFR-18/19/20, dan NFR-24.

## 1. Scheduler

Semua tugas berkala didaftarkan di `routes/console.php`. Di server, tambahkan
satu cron:

```cron
* * * * * cd /path/ke/siBis/backend && php artisan schedule:run >> /dev/null 2>&1
```

Dua tugas yang berjalan:

| Waktu | Perintah | Kegunaan |
|---|---|---|
| 02:30 setiap hari | `sibis:backup` | Backup database + prune retensi |
| Setiap 5 menit | `sibis:health` | Cek database & layanan RAG |

## 2. Backup (NFR-24)

```bash
cd backend
php artisan sibis:backup
```

- Disimpan di `backend/storage/app/backups/` (disk `backups`, **tidak** bisa diakses publik).
- Nama berkas: `sibis-YYYYMMDD-HHMMSS-<driver>.gz`.
- SQLite memakai `VACUUM INTO` (snapshot konsisten).
- MySQL/MariaDB memakai `mysqldump`; password lewat env `MYSQL_PWD`.
- Backup lebih tua dari `BACKUP_RETENTION_DAYS` (default 30) dihapus otomatis.

### Restore

SQLite:

```bash
gunzip -c backend/storage/app/backups/sibis-....sqlite.gz > backend/database/database.sqlite
```

MySQL:

```bash
gunzip -c backend/storage/app/backups/sibis-....mysql.gz | mysql -u user -p nama_database
```

Catat: RPO maksimal 24 jam (backup harian), RTO ditentukan kecepatan restore
server. Simpan salinan backup di luar server (object storage) untuk produksi.

## 3. Metrik & logging (NFR-18/19)

Setiap panggilan ke layanan RAG menulis satu baris JSON ke
`backend/storage/logs/metrics-YYYY-MM-DD.log`:

```json
{"message":"rag.ask","context":{"status":"ok","http_status":200,"duration_ms":842,"sources":3,"answer_chars":612,"at":"..."}}
```

Yang **tidak** dicatat: isi pertanyaan, jawaban, nama, atau email.

- `status`: `ok`, `error` (HTTP gagal), `exception` (timeout/koneksi).
- `duration_ms`: latensi panggilan LLM/RAG.
- Ambang lambat: `RAG_SLOW_MS` (default 10000). Di atas ambang dicatat
  `warning` di log utama.

## 4. Health check & alerting (NFR-20)

```bash
cd backend
php artisan sibis:health
```

Memeriksa `select 1` ke database dan `GET {RAG_URL}/health`.
Kegagalan dicatat level `critical` di log Laravel.

Untuk alerting nyata, arahkan log ke kanal eksternal, misalnya:

```env
LOG_STACK=daily,slack
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

## 5. Redaksi PII (NFR-16)

`backend/app/Services/PiiFilter.php` mengganti data berikut **pada salinan yang dikirim
ke layanan AI**:

| Data | Pengganti |
|---|---|
| Email | `[email]` |
| Nomor HP Indonesia (08xx/628xx/+628xx) | `[telepon]` |
| NIK 16 digit | `[nik]` |
| NPWP format 12.345.678.9-012.345 | `[npwp]` |

Pesan asli di database tetap utuh supaya riwayat pengguna tidak rusak.
Keterbatasan: nama, alamat, dan nomor rekening belum dikenali; jangan kirim
data itu ke chatbot.

## 6. Catatan deployment

- NFR-10 (HTTPS/TLS): aktifkan HTTPS di reverse proxy (nginx/Caddy). Middleware
  `SecurityHeaders` sudah mengirim HSTS saat request terdeteksi secure.
- NFR-23: jalankan FastAPI hanya di jaringan privat dan samakan `RAG_TOKEN`.
- NFR-24: jadwalkan salinan backup ke luar server (S3/rsync) untuk mitigasi
  kehilangan server.
