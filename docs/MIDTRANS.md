# Midtrans Sandbox — Panduan Singkat

Dipakai untuk RF-07 (berlangganan premium) dan RF-21 (monitoring pembayaran).

## 1. Ambil kunci sandbox

1. Daftar/masuk ke <https://dashboard.sandbox.midtrans.com>.
2. Buka **Settings → Access Keys**.
3. Salin **Server Key** dan **Client Key** (mode Sandbox).

## 2. Isi environment

Tambahkan/ubah di `backend/.env`:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_PREMIUM_PRICE=49000
MIDTRANS_PREMIUM_DAYS=30
```

Lalu:

```bash
cd backend
php artisan config:clear
```

Tanpa `MIDTRANS_SERVER_KEY`, tombol **Bayar dengan Midtrans** otomatis
dinonaktifkan dan checkout ditolak dengan pesan yang jelas (tidak ada
transaksi yatim).

## 3. Alur yang dipakai

1. Pengguna membuka `/premium` lalu menekan **Bayar dengan Midtrans**.
2. `PremiumController@checkout` membuat baris `transactions` berstatus
   `pending`, memanggil Snap, lalu mengarahkan ke `redirect_url` Midtrans.
3. Setelah membayar, Midtrans mengembalikan pengguna ke
   `/premium/selesai?order_id=...`.
4. Halaman selesai menanyakan status langsung ke API Midtrans
   (`GET /v2/{order_id}/status`), lalu mengaktifkan premium bila lunas.
   Ini penting karena webhook tidak bisa menjangkau `localhost`.
5. Di produksi, webhook `POST /midtrans/notification` juga aktif.
   Keasliannya diverifikasi lewat `signature_key` (SHA-512), dan premium
   hanya diperpanjang sekali per transaksi.

## 4. Webhook di produksi

Isi **Settings → Configuration → Payment Notification URL** dengan:

```
https://domain-anda/midtrans/notification
```

Untuk mencoba webhook di lokal, gunakan tunnel (mis. ngrok) dan arahkan URL
tersebut ke `/midtrans/notification`.

## 5. Pindah ke produksi

```env
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=<server key produksi>
MIDTRANS_CLIENT_KEY=<client key produksi>
```

Lalu `php artisan config:clear`. Tidak ada perubahan kode.

## 6. Yang diuji otomatis

`backend/tests/Feature/PremiumTest.php`:

- checkout membuat transaksi pending + redirect Snap;
- checkout tanpa kunci tidak membuat transaksi;
- webhook lunas mengaktifkan premium dan tidak memperpanjang dua kali;
- signature palsu ditolak (403);
- halaman selesai menyinkronkan status dari API;
- admin bisa melihat monitoring, user biasa 403;
- kuota premium 100/hari dan kembali 5/hari setelah kedaluwarsa.
