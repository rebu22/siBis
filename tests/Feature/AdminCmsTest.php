<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Risiko yang dijaga di sini: CMS admin tidak boleh bisa dipakai user biasa,
 * slug harus tetap unik walau dikosongkan, dan artikel draf tidak boleh
 * langsung tampil di halaman publik. Jalankan: php artisan test --filter=AdminCms
 */
class AdminCmsTest extends TestCase
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

    public function test_pengguna_biasa_tidak_bisa_membuka_monitoring_atau_menulis_artikel(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($user)->get('/admin/artikel')->assertForbidden();
        $this->actingAs($user)->post('/admin/artikel', [
            'title' => 'Artikel liar',
            'body' => str_repeat('Isi artikel yang cukup panjang. ', 3),
            'status' => 'published',
        ])->assertForbidden();

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_admin_bisa_melihat_daftar_pengguna_beserta_pemakaian_kuota(): void
    {
        $admin = $this->admin();
        $this->user(['plan' => 'premium', 'quota_used' => 7, 'quota_date' => now()]);

        $this->actingAs($admin)->get('/admin/pengguna')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users')
                ->where('summary.total', 2)
                ->where('summary.premium', 1)
                ->where('summary.asked_today', 7)
                ->has('users.data', 2));
    }

    public function test_artikel_baru_disimpan_dengan_slug_otomatis_dan_tanggal_terbit(): void
    {
        $admin = $this->admin();
        $body = str_repeat('Isi artikel yang membahas pendirian badan usaha. ', 3);

        $this->actingAs($admin)->post('/admin/artikel', [
            'title' => 'Memilih Badan Usaha',
            'category' => 'Badan Usaha',
            'excerpt' => 'Ringkasan.',
            'body' => $body,
            'status' => 'published',
        ])->assertRedirect();

        $article = Article::firstOrFail();

        $this->assertSame('memilih-badan-usaha', $article->slug);
        $this->assertSame($admin->id, $article->user_id);
        $this->assertNotNull($article->published_at);

        // Draf tidak boleh muncul di halaman publik.
        $this->actingAs($admin)->post('/admin/artikel', [
            'title' => 'Masih Draf',
            'body' => $body,
            'status' => 'draft',
        ])->assertRedirect();

        $this->get('/artikel')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Articles/Index')
            ->has('articles.data', 1));
    }

    public function test_judul_sama_menghasilkan_slug_berbeda(): void
    {
        $admin = $this->admin();
        $payload = [
            'title' => 'Kewajiban Data Pribadi',
            'body' => str_repeat('Isi artikel yang membahas data pribadi pelanggan. ', 2),
            'status' => 'draft',
        ];

        $this->actingAs($admin)->post('/admin/artikel', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/artikel', $payload)->assertRedirect();

        $this->assertSame(
            ['kewajiban-data-pribadi', 'kewajiban-data-pribadi-2'],
            Article::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_admin_bisa_mengubah_dan_menghapus_artikel(): void
    {
        $admin = $this->admin();
        $body = str_repeat('Isi artikel yang membahas kontrak kerja sama usaha. ', 2);

        $this->actingAs($admin)->post('/admin/artikel', [
            'title' => 'Kontrak Kerja Sama',
            'body' => $body,
            'status' => 'draft',
        ])->assertRedirect();

        $article = Article::firstOrFail();

        $this->actingAs($admin)->patch("/admin/artikel/{$article->slug}", [
            'title' => 'Kontrak Kerja Sama (Revisi)',
            'slug' => $article->slug,
            'body' => $body,
            'status' => 'published',
        ])->assertRedirect();

        $article->refresh();
        $this->assertSame('Kontrak Kerja Sama (Revisi)', $article->title);
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);

        $this->actingAs($admin)->delete("/admin/artikel/{$article->slug}")->assertRedirect();
        $this->assertDatabaseCount('articles', 0);
    }
}
