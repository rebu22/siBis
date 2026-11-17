<?php

namespace Tests\Feature;

use App\Models\AnswerReport;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\CompanyProfile;
use App\Models\Regulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Fase A: hapus riwayat (RF-24), CMS profil (RF-17), tindak lanjut laporan (RF-26),
 * dan audit log (NFR-21).
 * Jalankan: php artisan test --filter=AdminOperations
 */
class AdminOperationsTest extends TestCase
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

    private function sesi(User $user, string $title = 'Konsultasi'): ChatSession
    {
        $session = ChatSession::create(['user_id' => $user->id, 'title' => $title]);
        ChatMessage::create(['chat_session_id' => $session->id, 'role' => 'user', 'body' => 'Pertanyaan']);

        return $session;
    }

    public function test_user_bisa_menghapus_satu_dan_semua_riwayat_miliknya(): void
    {
        $me = $this->user();
        $satu = $this->sesi($me, 'Pertama');
        $dua = $this->sesi($me, 'Kedua');

        $this->actingAs($me)->delete("/riwayat/{$satu->id}")
            ->assertRedirect(route('history.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('chat_sessions', ['id' => $satu->id]);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseHas('chat_sessions', ['id' => $dua->id]);

        $this->actingAs($me)->delete('/riwayat')
            ->assertRedirect(route('history.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('chat_sessions', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_user_tidak_bisa_menghapus_riwayat_orang_lain(): void
    {
        $lain = $this->sesi($this->user(), 'Milik orang lain');

        $this->actingAs($this->user())->delete("/riwayat/{$lain->id}")->assertNotFound();

        $this->assertDatabaseHas('chat_sessions', ['id' => $lain->id]);
    }

    public function test_admin_bisa_mengubah_profil_perusahaan_dan_tercatat_di_audit(): void
    {
        $admin = $this->admin();
        CompanyProfile::create(['name' => 'Nama Lama']);

        $this->actingAs($admin)->patch('/admin/profil', [
            'name' => 'siBis Baru',
            'vision' => 'Visi baru.',
            'mission' => 'Misi baru.',
            'services' => "Konsultasi\nKebijakan",
            'contact_email' => 'halo@sibis.test',
            'contact_phone' => '021-123',
            'address' => 'Jakarta',
        ])->assertRedirect()->assertSessionHas('success');

        $profile = CompanyProfile::current();

        $this->assertSame('siBis Baru', $profile->name);
        $this->assertSame('Visi baru.', $profile->vision);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'company_profile.update',
        ]);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_atau_mengubah_profil_perusahaan(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin/profil')->assertForbidden();
        $this->actingAs($user)->patch('/admin/profil', ['name' => 'Dibajak'])->assertForbidden();
        $this->actingAs($user)->get('/admin/laporan')->assertForbidden();
        $this->actingAs($user)->get('/admin/audit')->assertForbidden();
    }

    public function test_admin_bisa_menindaklanjuti_laporan_jawaban(): void
    {
        $admin = $this->admin();
        $pelapor = $this->user();
        $pesan = $this->sesi($pelapor)->messages()->where('role', 'user')->firstOrFail();

        $laporan = AnswerReport::create([
            'chat_message_id' => $pesan->id,
            'user_id' => $pelapor->id,
            'reason' => 'usang',
            'note' => 'Peraturan sudah dicabut.',
        ]);

        $this->actingAs($admin)->get('/admin/laporan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Reports')
                ->has('reports.data', 1)
                ->where('reports.data.0.reporter.email', $pelapor->email)
                ->where('summary.baru', 1));

        $this->actingAs($admin)->patch("/admin/laporan/{$laporan->id}", [
            'status' => 'selesai',
            'note' => 'Sudah diganti dengan rujukan terbaru.',
        ])->assertRedirect()->assertSessionHas('success');

        $laporan->refresh();
        $this->assertSame('selesai', $laporan->status);
        $this->assertSame('Sudah diganti dengan rujukan terbaru.', $laporan->note);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'answer_report.update',
        ]);
    }

    public function test_status_laporan_di_luar_daftar_ditolak(): void
    {
        $admin = $this->admin();
        $laporan = AnswerReport::create([
            'chat_message_id' => $this->sesi($this->user())->messages()->firstOrFail()->id,
            'user_id' => $this->user()->id,
            'reason' => 'lainnya',
        ]);

        $this->actingAs($admin)->patch("/admin/laporan/{$laporan->id}", ['status' => 'diabaikan'])
            ->assertSessionHasErrors('status');

        $this->assertSame('baru', $laporan->fresh()->status);
    }

    public function test_audit_log_mencatat_perubahan_kebijakan(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/rag/ingest/pdf' => Http::response(['id' => 'doc1', 'title' => 'Kebijakan', 'chunks' => 3]),
            '*/rag/documents/*' => Http::response(['deleted' => 3]),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/kebijakan', [
            'file' => UploadedFile::fake()->create('kebijakan.pdf', 120, 'application/pdf'),
            'title' => 'Kebijakan Audit',
            'status' => 'published',
        ])->assertRedirect();

        $regulation = Regulation::firstOrFail();

        $this->actingAs($admin)->delete("/admin/kebijakan/{$regulation->slug}")->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'regulation.create']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'regulation.delete']);

        $this->actingAs($admin)->get('/admin/audit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Audit')
                ->has('logs.data', 2));
    }
}
