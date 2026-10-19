<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pemeriksaan yang mewakili risiko utama desain: injeksi SQL pada pencarian,
 * IDOR pada riwayat/penilaian, kuota saat layanan AI mati, brute-force login,
 * dan gerbang admin. Jalankan: php artisan test --filter=CoreFlow
 */
class CoreFlowTest extends TestCase
{
    use RefreshDatabase;

    /** role/plan/email_verified_at tidak mass-assignable, jadi harus lewat forceFill. */
    private function user(array $attributes = []): User
    {
        $user = User::factory()->create(['email' => Str::uuid().'@sibis.test']);
        $user->forceFill($attributes + ['email_verified_at' => now()])->save();

        return $user;
    }

    private function article(string $status = 'published', string $title = 'Panduan Mendirikan PT'): Article
    {
        return Article::create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'category' => 'Badan Usaha',
            'excerpt' => 'Ringkasan singkat.',
            'body' => 'Isi artikel.',
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    public function test_pencarian_artikel_kebal_injeksi_sql_dan_tidak_membocorkan_draft(): void
    {
        $this->article();
        $this->article('draft', 'Draf Internal');

        // Muatan klasik: bila ada penggabungan string SQL, ini akan mengembalikan semua baris.
        $this->get('/artikel?q='.urlencode("' OR 1=1 --"))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Articles/Index')
                ->has('articles.data', 0));

        $this->get('/artikel?q='.urlencode('%'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 1));

        $this->get('/artikel?q=Panduan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 1));

        $this->get('/artikel/'.Str::slug('Draf Internal'))->assertNotFound();
    }

    public function test_riwayat_dan_penilaian_milik_orang_lain_tidak_bisa_diakses(): void
    {
        $other = $this->user();
        $session = ChatSession::create(['user_id' => $other->id, 'title' => 'Milik orang lain']);
        $message = ChatMessage::create(['chat_session_id' => $session->id, 'role' => 'assistant', 'body' => 'Jawaban']);

        $me = $this->user();

        $this->actingAs($me)->get("/riwayat/{$session->id}")->assertNotFound();
        $this->actingAs($me)->post("/jawaban/{$message->id}/nilai", ['score' => 5])->assertNotFound();
        $this->actingAs($me)->post("/jawaban/{$message->id}/lapor", ['reason' => 'usang'])->assertNotFound();

        $this->actingAs($other)->get("/riwayat/{$session->id}")->assertOk();
    }

    public function test_jawaban_degraded_tidak_memotong_kuota_dan_tidak_menyimpan_sumber(): void
    {
        // Satu fake untuk kedua skenario: layanan belum siap (503) lalu jawaban normal.
        Http::fake(fn (Request $request) => Str::contains($request->data()['question'] ?? '', 'lisan')
            ? Http::response([
                'answer' => 'Perjanjian sah bila memenuhi syarat subjektif dan objektif.',
                'sources' => [['title' => 'KUH Perdata Pasal 1320', 'url' => 'https://example.test/kuhp', 'snippet' => 'Empat syarat sah perjanjian.']],
            ])
            : Http::response(['detail' => 'LLM belum dikonfigurasi'], 503));

        $user = $this->user();

        $this->actingAs($user)->post('/konsultasi', ['question' => 'Apa syarat sah perjanjian?'])->assertRedirect();

        $message = ChatMessage::where('role', 'assistant')->firstOrFail();
        $this->assertTrue($message->degraded);
        $this->assertSame([], $message->sources);
        $this->assertSame(User::DAILY_QUOTA['free'], $user->fresh()->quotaRemaining(), 'kuota tidak boleh terpotong saat degraded');

        $this->actingAs($user)->post('/konsultasi', ['question' => 'Bagaimana dengan perjanjian lisan?'])->assertRedirect();

        $ok = ChatMessage::where('role', 'assistant')->where('degraded', false)->firstOrFail();
        $this->assertSame('KUH Perdata Pasal 1320', $ok->sources[0]['title']);
        $this->assertSame(User::DAILY_QUOTA['free'] - 1, $user->fresh()->quotaRemaining());
    }

    public function test_login_terkunci_setelah_percobaan_berulang(): void
    {
        $user = $this->user();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah-terus']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'salah-terus'])
            ->assertSessionHasErrors('email');

        // Kredensial yang benar pun ditolak selama kunci belum lewat.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_area_admin_wajib_admin_dan_otp_baru(): void
    {
        $this->actingAs($this->user())->get('/admin')->assertForbidden();

        $admin = $this->user(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/verifikasi');

        $admin->forceFill(['two_factor_at' => now()])->save();
        $this->actingAs($admin)->get('/admin')->assertOk();

        // OTP lama/milik user lain tidak bisa dipakai: kode dikirim ke email admin.
        $this->actingAs($admin)->post('/admin/verifikasi', ['code' => '000000'])
            ->assertSessionHas('error');
    }

    public function test_register_membuat_profil_usaha_dan_selalu_plan_free(): void
    {
        $this->post('/register', [
            'name' => 'Pelaku Usaha',
            'email' => 'baru@sibis.test',
            'password' => 'kata-sandi-panjang',
            'password_confirmation' => 'kata-sandi-panjang',
            'business_name' => 'CV Baru Jaya',
            'city' => 'Medan',
            'role' => 'admin', // diabaikan: role tidak mass-assignable
        ])->assertRedirect(route('consultation.index'));

        $user = User::where('email', 'baru@sibis.test')->firstOrFail();

        $this->assertSame('free', $user->plan);
        $this->assertSame('user', $user->role);
        $this->assertSame('CV Baru Jaya', $user->businessProfile->business_name);
    }

    public function test_halaman_galat_pakai_tema_sendiri_bukan_halaman_putih_laravel(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Galat')
            ->assertSee('#f8fafc', false)
            ->assertDontSee('bg-white');
    }
}
