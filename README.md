# siBis — Anti-Slop Guide

Acuan agar website siBis tidak terlihat "AI banget".
Baca ini sebelum generate copy / desain / kode pakai AI.

## 1. Prinsip Utama
Spesifik > generik. Bukti > klaim. Satu karakter > semua gaya.

Kalau tulisan/desain bisa dipakai untuk 1000 website lain, itu slop. Tolak.

## 2. Copywriting: Kata Terlarang
JANGAN pakai (atau variasinya):
`delve, unlock, elevate, seamless, cutting-edge, revolutionize, game-changer, state-of-the-art, in today's fast-paced world, in the realm of, tailored solution, robust solution, innovative solution`

JANGAN pakai pola:
- Hero: "Selamat datang di masa depan [X]"
- Subhero: "Revolusikan bisnis Anda dengan solusi inovatif kami"
- 3 kartu: "Efisien / Terpercaya / Inovatif" tanpa angka
- CTA: "Get Started / Learn More" doang
- Em-dash `—` tiap kalimat + emoji tiap heading

WAJIB:
- Maks 15 kata per kalimat
- Setiap klaim ditempel fakta: angka, nama, durasi, harga, lokasi
- Tombol pakai kata kerja spesifik: `Lihat Demo Kasir`, `Cek Harga per Cabang`, bukan `Mulai Sekarang`
- Bahasa: Indonesia santai, boleh campur istilah warung/toko asli

Contoh:
❌ Slop: `Rasakan kemudahan seamless dengan solusi kasir cutting-edge kami untuk meningkatkan bisnis Anda.`
✅ Fix: `Kasir siBis jalan offline. Cetak struk 3 detik, stok berkurang otomatis.`

## 3. Visual / UI: Template Terlarang
JANGAN default AI:
- Hero centered + badge "✨ Introducing v2.0" + gradient ungu-biru + 2 tombol
- 3 kartu fitur ikon generik
- Font Inter / Poppins / Roboto tanpa ubahan
- Ilustrasi 3D kaca / robot AI / Unsplash random
- Semua section `text-center`, `rounded-2xl`, `shadow-xl`, glassmorphism di mana-mana

WAJIB siBis:
- Warna: 1 warna utama berani + hitam/putih. Jangan ungu-biru default. Contoh: hijau warung `#16A34A` + krem `#FFF7ED` + hitam.
- Font: 1 display + 1 body, beda karakter. Contoh: `Space Grotesk / Plus Jakarta Sans` + `Inter` untuk body kecil.
- Layout: asimetris. Hero kiri-berat + foto/screenshot kanan. Tidak semua centered.
- Gambar: foto asli / screenshot produk sendiri. Kasih `noise`, border tegas, bukan gradient halus.
- Detail manusia: typo kecil yang disengaja, stiker harga, nota tangan, testimoni nama asli.

## 4. Prompt AI yang Boleh
Tempel ini ke setiap prompt generate konten:

```
Konteks: siBis, [jelaskan produk dalam 1 kalimat spesifik].
Batasan: dilarang kata delve, unlock, seamless, revolutionize, elevate.
Maks 15 kata/kalimat. Wajib pakai 1 angka/fakta dari input.
Nada: santai, Indonesia, seperti pemilik warung.
Jika tidak ada fakta, tulis [butuh data], jangan mengarang.
Kasih 2 opsi dengan sudut pandang beda.
```

Setting: `temperature 0.6-0.7`, selalu kasih 2-3 contoh tulisanmu sebagai few-shot.

## 5. Checklist Sebelum Push
- [ ] Bisa tebak ini web siBis tanpa lihat logo? Kalau tidak, revisi.
- [ ] Ada minimal 1 angka / nama / screenshot asli di atas fold?
- [ ] Tidak ada kata terlarang di atas?
- [ ] Tidak ada gradient ungu-biru + hero centered generik?
- [ ] Tombol spesifik, bukan Get Started?
- [ ] Minta 1 orang baca 5 detik: apa yang dijual + berapa harganya? Kalau bingung, revisi.

## 6. Struktur Rencana siBis
- `/` landing spesifik, bukan generik
- `/demo` screenshot/video asli
- `/harga` angka jelas
- `/changelog` progress update (ini yang kamu push ke GitHub tiap hari)

---
Aturan main: lebih baik jelek tapi khas, daripada rapi tapi mirip 1000 web AI.
