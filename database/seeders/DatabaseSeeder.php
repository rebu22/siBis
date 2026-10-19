<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::query()->delete();
        CompanyProfile::create([
            'name' => 'siBis — Sistem Informasi Konsultasi Hukum Bisnis',
            'vision' => 'Membuat informasi hukum bisnis dasar mudah dijangkau pelaku usaha kecil di Indonesia.',
            'mission' => 'Menyediakan konsultasi hukum bisnis berbasis AI yang selalu menyertakan rujukan regulasi, disertai artikel edukasi yang dapat dipahami tanpa latar belakang hukum.',
            'services' => "Konsultasi hukum bisnis berbasis AI\nArtikel edukasi hukum\nRujukan regulasi pada setiap jawaban",
            'contact_email' => 'halo@sibis.test',
            'contact_phone' => '021-0000000',
            'address' => 'Jl. Contoh No. 1, Jakarta',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin siBis',
            'email' => 'admin@sibis.test',
        ]);
        $admin->forceFill([
            'role' => 'admin',
            'plan' => 'premium',
            'email_verified_at' => now(),
            'password' => 'password',
        ])->save();

        $demo = User::factory()->create([
            'name' => 'Pelaku Usaha Demo',
            'email' => 'usaha@sibis.test',
        ]);
        $demo->forceFill(['email_verified_at' => now(), 'password' => 'password'])->save();
        $demo->businessProfile()->create([
            'business_name' => 'CV Contoh Jaya',
            'business_type' => 'Perdagangan',
            'city' => 'Bandung',
        ]);

        $demo->chatSessions()->create(['title' => 'Syarat kontrak kerja sama'])->messages()->createMany([
            ['role' => 'user', 'body' => 'Apa saja yang perlu saya perhatikan sebelum menandatangani perjanjian kerja sama usaha?'],
            ['role' => 'assistant', 'body' => 'Sebelum menandatangani, pastikan identitas dan kewenangan pihak yang menandatangani jelas, lingkup kerja sama terukur, serta mekanisme pembayaran dan penyelesaian sengketa tertulis. Contoh jawaban ini belum berasal dari basis pengetahuan, jadi tidak boleh dijadikan rujukan.', 'degraded' => true],
        ]);

        $articles = [
            [
                'title' => 'Memilih Bentuk Badan Usaha untuk Usaha Kecil',
                'category' => 'Badan Usaha',
                'excerpt' => 'Perbedaan mendasar usaha perseorangan, CV, dan perseroan terbatas dari sisi tanggung jawab dan administrasi.',
                'body' => <<<'TXT'
Bentuk badan usaha menentukan sejauh mana harta pribadi pemilik terpisah dari tanggung jawab usaha, sekaligus menentukan kewajiban administrasi yang harus dipenuhi setiap tahun.

Usaha perseorangan paling sederhana: pendiriannya tidak memerlukan akta, tetapi pemilik bertanggung jawab atas seluruh kewajiban usaha. CV dan perseroan terbatas memerlukan akta pendirian dan pengesahan dari instansi berwenang, dengan konsekuensi tanggung jawab yang lebih terbatas bagi pemiliknya.

Artikel ini bersifat edukasi umum dan bukan nasihat hukum atas kasus tertentu. Untuk keputusan usaha Anda, konsultasikan dokumen dan kondisi usaha secara lengkap dengan advokat atau notaris.
TXT,
            ],
            [
                'title' => 'Klausul Perjanjian Kerja Sama yang Sering Terlewat',
                'category' => 'Perjanjian',
                'excerpt' => 'Bagian perjanjian yang paling sering menimbulkan sengketa: lingkup kerja, jangka waktu, dan cara penyelesaian sengketa.',
                'body' => <<<'TXT'
Tiga klausul yang paling sering memicu perselisihan adalah lingkup pekerjaan atau kerja sama, jangka waktu beserta mekanisme pengakhiran, dan cara penyelesaian sengketa.

Pastikan setiap klausul dapat diukur: siapa mengerjakan apa, dalam batas waktu berapa, dengan standar hasil seperti apa. Perjanjian yang memakai istilah umum tanpa ukuran akan sulit dibuktikan saat terjadi perbedaan penafsiran.

Artikel ini bukan nasihat hukum untuk kasus tertentu. Mintalah advokat menelaah rancangan perjanjian sebelum ditandatangani.
TXT,
            ],
            [
                'title' => 'Kewajiban Pelaku Usaha atas Data Pribadi Pelanggan',
                'category' => 'Kepatuhan',
                'excerpt' => 'Ringkasan kewajiban dasar pengelola data pribadi berdasarkan UU No. 27 Tahun 2022.',
                'body' => <<<'TXT'
Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi menempatkan pelaku usaha yang mengelola data pribadi sebagai pengendali dan pemroses data, dengan kewajiban antara lain meminta persetujuan yang sah, membatasi pengumpulan sesuai tujuan, serta menjaga kerahasiaan data.

Termasuk data pribadi adalah nama, alamat, dan data percakapan pengguna yang melekat pada identitas seseorang. Karena itu, layanan konsultasi yang menyimpan riwayat percakapan pengguna juga tunduk pada kewajiban ini.

Artikel ini bersifat ringkas dan bukan nasihat hukum. Untuk menilai kepatuhan usaha Anda, lakukan penilaian bersama penasihat hukum.
TXT,
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article + [
                'user_id' => $admin->id,
                'slug' => Str::slug($article['title']),
                'status' => 'published',
                'published_at' => now()->subDays(random_int(1, 30)),
            ]);
        }

        $this->command?->info('Admin: admin@sibis.test / password — User: usaha@sibis.test / password (ganti sebelum dipakai di luar lokal).');
    }
}
