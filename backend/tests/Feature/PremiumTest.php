<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Fase C: premium lewat Midtrans (RF-07/RF-21).
 * Risiko yang dijaga: checkout tanpa kunci tidak membuat transaksi, webhook palsu
 * ditolak, pembayaran lunas hanya memperpanjang premium sekali, dan user biasa
 * tidak bisa membuka monitoring pembayaran.
 * Jalankan: php artisan test --filter=Premium
 */
class PremiumTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.midtrans.server_key', self::SERVER_KEY);
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');
        config()->set('services.midtrans.production', false);
        config()->set('services.midtrans.premium_price', 49000);
        config()->set('services.midtrans.premium_days', 30);
    }

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

    private function transaksi(User $user, string $status = 'pending'): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'order_id' => 'SIBIS-TEST-'.Str::upper(Str::random(8)),
            'plan' => 'premium',
            'amount' => 49000,
            'days' => 30,
            'status' => $status,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function signature(array $payload): string
    {
        return hash('sha512', $payload['order_id']
            .$payload['status_code']
            .$payload['gross_amount']
            .self::SERVER_KEY);
    }

    public function test_checkout_membuat_transaksi_pending_dan_mengarahkan_ke_midtrans(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-1',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-1',
            ]),
        ]);

        $user = $this->user();

        $this->actingAs($user)->post('/premium')
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-1');

        $transaksi = Transaction::firstOrFail();

        $this->assertSame('pending', $transaksi->status);
        $this->assertSame(49000, $transaksi->amount);
        $this->assertSame(30, $transaksi->days);
        $this->assertSame('snap-token-1', $transaksi->snap_token);

        Http::assertSent(function (Request $request) use ($transaksi) {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request['transaction_details']['order_id'] === $transaksi->order_id
                && $request['transaction_details']['gross_amount'] === 49000;
        });
    }

    public function test_checkout_tanpa_kunci_midtrans_tidak_membuat_transaksi(): void
    {
        config()->set('services.midtrans.server_key', '');

        $this->actingAs($this->user())->post('/premium')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_webhook_lunas_mengaktifkan_premium_sekali_saja(): void
    {
        $user = $this->user();
        $transaksi = $this->transaksi($user);

        $payload = [
            'order_id' => $transaksi->order_id,
            'status_code' => '200',
            'gross_amount' => '49000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ];
        $payload['signature_key'] = $this->signature($payload);

        $this->postJson('/midtrans/notification', $payload)->assertOk();

        $transaksi->refresh();
        $user->refresh();

        $this->assertSame('settlement', $transaksi->status);
        $this->assertSame('qris', $transaksi->payment_type);
        $this->assertNotNull($transaksi->paid_at);
        $this->assertSame('premium', $user->plan);
        $this->assertSame('premium', $user->effectivePlan());
        $this->assertTrue($user->premium_until->between(now()->addDays(29), now()->addDays(31)));
        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction.paid']);

        $until = $user->premium_until->toDateTimeString();

        // Webhook diulang tidak boleh menambah 30 hari lagi.
        $this->postJson('/midtrans/notification', $payload)->assertOk();

        $this->assertSame($until, $user->fresh()->premium_until->toDateTimeString());
    }

    public function test_webhook_dengan_signature_salah_ditolak(): void
    {
        $user = $this->user();
        $transaksi = $this->transaksi($user);

        $this->postJson('/midtrans/notification', [
            'order_id' => $transaksi->order_id,
            'status_code' => '200',
            'gross_amount' => '49000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'palsu',
        ])->assertForbidden();

        $this->assertSame('pending', $transaksi->fresh()->status);
        $this->assertSame('free', $user->fresh()->effectivePlan());
    }

    public function test_halaman_selesai_menanyakan_status_ke_midtrans(): void
    {
        $user = $this->user();
        $transaksi = $this->transaksi($user);

        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $transaksi->order_id,
                'transaction_status' => 'settlement',
                'status_code' => '200',
                'gross_amount' => '49000.00',
                'payment_type' => 'bank_transfer',
            ]),
        ]);

        $this->actingAs($user)
            ->get('/premium/selesai?order_id='.$transaksi->order_id)
            ->assertRedirect(route('premium.index'))
            ->assertSessionHas('success');

        $this->assertSame('premium', $user->fresh()->effectivePlan());
        $this->assertSame('settlement', $transaksi->fresh()->status);
    }

    public function test_admin_bisa_melihat_monitoring_pembayaran_dan_user_biasa_tidak(): void
    {
        $admin = $this->admin();
        $pembeli = $this->user(['name' => 'Pembeli Premium']);
        $transaksi = $this->transaksi($pembeli, 'settlement');
        $transaksi->forceFill(['paid_at' => now(), 'payment_type' => 'qris'])->save();

        $this->actingAs($admin)->get('/admin/pembayaran')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Transactions')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.buyer.email', $pembeli->email)
                ->where('summary.paid', 1)
                ->where('summary.revenue', 49000)
                ->where('summary.pending', 0));

        $this->actingAs($pembeli)->get('/admin/pembayaran')->assertForbidden();
    }

    public function test_kuota_premium_100_dan_kembali_5_setelah_kedaluwarsa(): void
    {
        $user = $this->user(['plan' => 'premium']);
        $user->forceFill(['premium_until' => now()->addDays(10)])->save();

        $this->assertSame(100, $user->fresh()->dailyQuota());
        $this->assertSame('premium', $user->fresh()->effectivePlan());

        $user->forceFill(['premium_until' => now()->subDay()])->save();

        $this->assertSame(5, $user->fresh()->dailyQuota());
        $this->assertSame('free', $user->fresh()->effectivePlan());
    }

    public function test_halaman_premium_menampilkan_paket_dan_riwayat(): void
    {
        // Tamu boleh melihat halaman premium; pembelian tetap wajib akun.
        $this->get('/premium')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Premium/Index')
                ->where('isGuest', true)
                ->where('plan', 'free'));
        $this->post('/premium')->assertRedirect(route('login'));

        $user = $this->user();
        $this->transaksi($user);

        $this->actingAs($user)->get('/premium')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Premium/Index')
                ->where('plan', 'free')
                ->where('price', 49000)
                ->where('days', 30)
                ->where('configured', true)
                ->has('transactions', 1));
    }
}
