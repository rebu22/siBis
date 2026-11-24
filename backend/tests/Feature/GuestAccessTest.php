<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Akses pengunjung tanpa akun: konsultasi, riwayat, premium (lihat saja),
 * dan hubungi admin. Identitas tamu = cookie 30 hari.
 */
class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return Str::random(40);
    }

    private function fakeRag(): void
    {
        Http::fake(['*' => Http::response([
            'answer' => 'Jawaban edukasi awal.',
            'sources' => [['title' => 'UU PT', 'url' => 'https://example.test', 'snippet' => 'Ringkas.']],
        ])]);
    }

    public function test_tamu_bisa_membuka_konsultasi_riwayat_premium_dan_bantuan(): void
    {
        $this->get('/konsultasi')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Consultation/Index')
                ->where('quota.plan', 'tamu')
                ->where('quota.limit', 5));

        $this->get('/riwayat')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('History/Index'));

        $this->get('/premium')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Premium/Index')
                ->where('isGuest', true));

        $this->get('/hubungi-admin')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Support/Contact')
                ->where('isGuest', true));
    }

    public function test_konsultasi_tamu_tersimpan_dan_riwayat_terpisah_per_cookie(): void
    {
        $this->fakeRag();
        $tamu = $this->token();
        $lain = $this->token();

        $this->withCookie('sibis_guest', $tamu)
            ->post('/konsultasi', ['question' => 'Apa syarat mendirikan PT?'])
            ->assertRedirect();

        $session = ChatSession::query()->sole();
        $this->assertNull($session->user_id);
        $this->assertSame($tamu, $session->guest_token);
        $this->assertSame(2, $session->messages()->count());

        $this->withCookie('sibis_guest', $tamu)->get('/riwayat')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions.data', 1));

        $this->withCookie('sibis_guest', $lain)->get('/riwayat')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions.data', 0));

        $this->withCookie('sibis_guest', $lain)->get("/riwayat/{$session->id}")->assertNotFound();
    }

    public function test_kuota_tamu_lima_pertanyaan_per_hari(): void
    {
        $this->fakeRag();
        $tamu = $this->token();

        for ($i = 1; $i <= 6; $i++) {
            $this->withCookie('sibis_guest', $tamu)
                ->post('/konsultasi', ['question' => "Pertanyaan nomor {$i}?"])
                ->assertRedirect();
        }

        // Permintaan keenam tidak dikirim ke model: hanya 5 pesan user yang tersimpan.
        $this->assertSame(5, ChatMessage::query()->where('role', 'user')->count());

        $this->withCookie('sibis_guest', $tamu)->get('/konsultasi')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('quota.remaining', 0));
    }

    public function test_tamu_bisa_menilai_jawabannya_sendiri_saja(): void
    {
        $this->fakeRag();
        $tamu = $this->token();

        $this->withCookie('sibis_guest', $tamu)
            ->post('/konsultasi', ['question' => 'Apa itu CV?']);

        $jawaban = ChatMessage::query()->where('role', 'assistant')->sole();

        $this->withCookie('sibis_guest', $this->token())
            ->post("/jawaban/{$jawaban->id}/nilai", ['score' => 5])
            ->assertNotFound();

        $this->withCookie('sibis_guest', $tamu)
            ->post("/jawaban/{$jawaban->id}/nilai", ['score' => 4])
            ->assertRedirect();

        $this->assertDatabaseHas('answer_ratings', ['chat_message_id' => $jawaban->id, 'guest_token' => $tamu, 'score' => 4]);
    }

    public function test_pesan_tamu_butuh_nama_dan_email(): void
    {
        $pesan = ['subject' => 'Tanya kuota', 'body' => 'Berapa batas kuota untuk tamu?'];

        $this->post('/hubungi-admin', $pesan)->assertSessionHasErrors(['name', 'email']);

        $this->post('/hubungi-admin', $pesan + ['name' => 'Tamu', 'email' => 'tamu@example.test'])
            ->assertRedirect(route('contact.create'));

        $tersimpan = ContactMessage::query()->sole();
        $this->assertNull($tersimpan->user_id);
        $this->assertSame('tamu@example.test', $tersimpan->email);
    }

    public function test_percakapan_tamu_lebih_dari_30_hari_dihapus(): void
    {
        $lama = ChatSession::create(['guest_token' => $this->token()]);
        $lama->forceFill(['created_at' => now()->subDays(31), 'updated_at' => now()->subDays(31)])->save();
        $baru = ChatSession::create(['guest_token' => $this->token()]);

        $this->artisan('sibis:purge-guests')->assertSuccessful();

        $this->assertDatabaseMissing('chat_sessions', ['id' => $lama->id]);
        $this->assertDatabaseHas('chat_sessions', ['id' => $baru->id]);
    }
}
