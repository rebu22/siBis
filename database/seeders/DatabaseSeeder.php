<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::query()->delete();
        CompanyProfile::create([
            'name' => 'siBis — Sistem Informasi Konsultasi Hukum Bisnis',
            'vision' => 'Membuat informasi hukum bisnis dasar mudah dijangkau pelaku usaha kecil di Indonesia.',
            'mission' => 'Menyediakan konsultasi hukum bisnis berbasis AI yang selalu menyertakan rujukan regulasi, disertai artikel edukasi yang dapat dipahami tanpa latar belakang hukum.',
            'services' => "Konsultasi hukum bisnis berbasis AI\nKebijakan & regulasi\nRujukan regulasi pada setiap jawaban",
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

        $this->command?->info('Admin: admin@sibis.test / password — User: usaha@sibis.test / password (ganti sebelum dipakai di luar lokal).');
    }
}
