<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Fase B: lapor bug (RF-08/RF-19) dan pesan ke admin (RF-09/RF-20).
 * Risiko yang dijaga: laporan bug terbuka untuk pengunjung tetapi tetap tervalidasi,
 * screenshot hanya menerima gambar, kotak masuk admin tertutup bagi non-admin,
 * dan pesan hanya bisa dikirim pengguna yang login.
 * Jalankan: php artisan test --filter=Support
 */
class SupportTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        $user = User::factory()->create(['email' => Str::uuid().'@sibis.test']);
        $user->forceFill($attributes + ['email_verified_at' => now()])->save();

        return $user;
    }

    private function admin(): User
    {
        return $this->user(['role' => 'admin', 'two_factor_at' => now()]);
    }

    /** GD tidak selalu terpasang di CI, jadi PNG 1x1 dibuat dari byte asli. */
    private function gambarPng(string $nama = 'bukti.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sibis');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $nama, 'image/png', null, true);
    }

    public function test_pengunjung_bisa_mengirim_laporan_bug_beserta_screenshot(): void
    {
        Storage::fake('public');

        $this->get('/lapor-bug')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Support/Bug')
                ->where('defaults.email', ''));

        $this->post('/lapor-bug', [
            'name' => 'Pengunjung',
            'email' => 'pengunjung@sibis.test',
            'description' => 'Tombol kirim di halaman konsultasi memunculkan galat 500.',
            'screenshot' => $this->gambarPng(),
        ])->assertRedirect(route('bug.create'))->assertSessionHas('success');

        $laporan = BugReport::firstOrFail();

        $this->assertSame('baru', $laporan->status);
        $this->assertNull($laporan->user_id);
        $this->assertNotNull($laporan->screenshot_path);
        Storage::disk('public')->assertExists($laporan->screenshot_path);
        $this->assertStringStartsWith('lapor-bug/', $laporan->screenshot_path);
    }

    public function test_form_bug_terisi_otomatis_untuk_pengguna_login_dan_menyimpan_user_id(): void
    {
        Storage::fake('public');
        $user = $this->user(['name' => 'Sari']);

        $this->actingAs($user)->get('/lapor-bug')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('defaults.name', 'Sari')
                ->where('defaults.email', $user->email));

        $this->actingAs($user)->post('/lapor-bug', [
            'email' => $user->email,
            'description' => 'Halaman riwayat kosong padahal ada percakapan.',
        ])->assertRedirect(route('bug.create'));

        $this->assertDatabaseHas('bug_reports', [
            'user_id' => $user->id,
            'screenshot_path' => null,
        ]);
    }

    public function test_laporan_bug_wajib_berisi_deskripsi_dan_screenshot_harus_gambar(): void
    {
        Storage::fake('public');

        $this->post('/lapor-bug', [
            'email' => 'bukan-email',
            'description' => 'pendek',
            'screenshot' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['email', 'description', 'screenshot']);

        $this->assertDatabaseCount('bug_reports', 0);
    }

    public function test_admin_bisa_menindaklanjuti_laporan_bug_dan_tercatat_di_audit(): void
    {
        $admin = $this->admin();
        $laporan = BugReport::create([
            'name' => 'Pengunjung',
            'email' => 'pengunjung@sibis.test',
            'description' => 'Gambar tidak muncul di halaman detail kebijakan.',
        ]);

        $this->actingAs($admin)->get('/admin/laporan-bug')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/BugReports')
                ->has('reports.data', 1)
                ->where('summary.baru', 1));

        $this->actingAs($admin)->patch("/admin/laporan-bug/{$laporan->id}", [
            'status' => 'selesai',
            'admin_note' => 'Sudah diperbaiki di build berikutnya.',
        ])->assertRedirect()->assertSessionHas('success');

        $laporan->refresh();
        $this->assertSame('selesai', $laporan->status);
        $this->assertSame('Sudah diperbaiki di build berikutnya.', $laporan->admin_note);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'bug_report.update',
        ]);
    }

    public function test_status_bug_di_luar_daftar_ditolak(): void
    {
        $admin = $this->admin();
        $laporan = BugReport::create([
            'email' => 'pengunjung@sibis.test',
            'description' => 'Contoh laporan bug yang valid.',
        ]);

        $this->actingAs($admin)->patch("/admin/laporan-bug/{$laporan->id}", ['status' => 'diabaikan'])
            ->assertSessionHasErrors('status');

        $this->assertSame('baru', $laporan->fresh()->status);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_kotak_masuk_admin(): void
    {
        $user = $this->user();
        $pesan = ContactMessage::create([
            'user_id' => $user->id,
            'subject' => 'Pesan',
            'body' => 'Isi pesan untuk pengujian akses admin.',
        ]);

        $this->actingAs($user)->get('/admin/laporan-bug')->assertForbidden();
        $this->actingAs($user)->get('/admin/pesan')->assertForbidden();
        $this->actingAs($user)->patch("/admin/pesan/{$pesan->id}", ['read' => true])->assertForbidden();
        $this->assertNull($pesan->fresh()->read_at);
    }

    public function test_pesan_ke_admin_wajib_login(): void
    {
        $this->get('/hubungi-admin')->assertRedirect(route('login'));
        $this->post('/hubungi-admin', [
            'subject' => 'Tanpa akun',
            'body' => 'Pesan ini seharusnya tidak masuk.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_pengguna_bisa_mengirim_pesan_dan_admin_menandainya_dibaca(): void
    {
        $user = $this->user();
        $admin = $this->admin();

        $this->actingAs($user)->get('/hubungi-admin')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Support/Contact'));

        $this->actingAs($user)->post('/hubungi-admin', [
            'subject' => 'Kuota konsultasi',
            'body' => 'Apakah kuota harian direset setiap tengah malam?',
        ])->assertRedirect(route('contact.create'))->assertSessionHas('success');

        $pesan = ContactMessage::firstOrFail();
        $this->assertSame($user->id, $pesan->user_id);
        $this->assertNull($pesan->read_at);

        $this->actingAs($admin)->get('/admin/pesan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Messages')
                ->has('messages.data', 1)
                ->where('summary.unread', 1)
                ->where('messages.data.0.sender.email', $user->email));

        $this->actingAs($admin)->patch("/admin/pesan/{$pesan->id}", ['read' => true])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertNotNull($pesan->fresh()->read_at);

        $this->actingAs($admin)->patch("/admin/pesan/{$pesan->id}", ['read' => false])
            ->assertRedirect();

        $this->assertNull($pesan->fresh()->read_at);
    }
}
