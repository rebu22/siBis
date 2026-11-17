<?php

namespace Tests\Feature;

use App\Models\Regulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Risiko yang dijaga: CMS admin tidak boleh dipakai user biasa, unggah wajib PDF,
 * slug tetap unik walau dikosongkan, draf tidak tampil publik, dan menghapus kebijakan
 * benar-benar mencabut rujukannya dari basis pengetahuan chatbot.
 * Jalankan: php artisan test --filter=AdminCms
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

    /** @return array<string, mixed> */
    private function payload(array $attributes = []): array
    {
        return $attributes + [
            'file' => UploadedFile::fake()->create('regulasi.pdf', 300, 'application/pdf'),
            'title' => 'Memilih Badan Usaha',
            'category' => 'Badan Usaha',
            'excerpt' => 'Ringkasan.',
            'status' => 'published',
        ];
    }

    private function fakeRag(string $docId = 'd0c1'): void
    {
        Http::fake([
            '*/rag/ingest/pdf' => Http::response(['id' => $docId, 'title' => 'Memilih Badan Usaha', 'chunks' => 7]),
            '*/rag/documents/*' => Http::response(['deleted' => 7]),
        ]);
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_monitoring_atau_menulis_kebijakan(): void
    {
        Http::fake();

        $user = $this->user();

        $this->actingAs($user)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($user)->get('/admin/kebijakan')->assertForbidden();
        $this->actingAs($user)->post('/admin/kebijakan', $this->payload())->assertForbidden();
        // Slug harus ada supaya yang diuji benar-benar gerbang admin, bukan 404 dari route binding.
        $tersimpan = Regulation::create([
            'title' => 'Kebijakan Rahasia', 'slug' => 'kebijakan-rahasia',
            'status' => 'published', 'published_at' => now(),
        ]);

        $this->actingAs($user)->delete("/admin/kebijakan/{$tersimpan->slug}")->assertForbidden();

        $this->assertDatabaseCount('regulations', 1);
        Http::assertNothingSent();
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

    public function test_unggah_pdf_menyimpan_berkas_sekaligus_mengindeksnya_ke_chatbot(): void
    {
        Storage::fake('public');
        $this->fakeRag('abc123');

        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload())->assertRedirect();

        $regulation = Regulation::firstOrFail();

        $this->assertSame('memilih-badan-usaha', $regulation->slug);
        $this->assertSame($admin->id, $regulation->user_id);
        $this->assertNotNull($regulation->published_at);
        $this->assertSame('abc123', $regulation->rag_doc_id);
        $this->assertSame('kebijakan/memilih-badan-usaha.pdf', $regulation->file_path);
        Storage::disk('public')->assertExists($regulation->file_path);

        Http::assertSent(fn (Request $request) => Str::endsWith($request->url(), '/rag/ingest/pdf')
            && $request->isMultipart());

        // Draf tidak boleh muncul di halaman publik.
        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload([
            'title' => 'Masih Draf',
            'status' => 'draft',
            'file' => UploadedFile::fake()->create('draf.pdf', 120, 'application/pdf'),
        ]))->assertRedirect();

        $this->get('/kebijakan')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Kebijakan/Index')
            ->has('regulations.data', 1));
    }

    public function test_hanya_pdf_yang_diterima_dan_layanan_mati_tidak_jadi_galat_500(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Http::fake();

        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload([
            'file' => UploadedFile::fake()->create('catatan.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]))->assertSessionHasErrors('file');

        Http::assertNothingSent();

        // Layanan mati: PDF tetap tersimpan dan kebijakan tetap ada, tapi ditandai belum terindeks.
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload([
            'title' => 'Kebijakan Tanpa Layanan',
        ]))->assertRedirect()->assertSessionHas('error');

        $regulation = Regulation::firstOrFail();
        $this->assertNull($regulation->rag_doc_id);
        Storage::disk('public')->assertExists($regulation->file_path);
    }

    public function test_alasan_gagal_indeks_dari_layanan_diteruskan_apa_adanya(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/rag/ingest/pdf' => Http::response(['detail' => 'PDF tidak memuat teks yang bisa dibaca (hasil pindai?).'], 422),
        ]);

        $this->actingAs($this->admin())->post('/admin/kebijakan', $this->payload())
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, 'hasil pindai'));
    }

    public function test_kebijakan_belum_terindeks_bisa_diindeks_ulang_tanpa_unggah_ulang(): void
    {
        Storage::fake('public');
        $this->fakeRag('ulang123');

        $regulation = Regulation::create([
            'title' => 'Kebijakan Pindai', 'slug' => 'kebijakan-pindai', 'status' => 'published',
            'published_at' => now(), 'file_path' => 'kebijakan/kebijakan-pindai.pdf', 'file_size' => 1024,
        ]);
        Storage::disk('public')->put($regulation->file_path, '%PDF-1.4');

        $this->actingAs($this->admin())
            ->post("/admin/kebijakan/{$regulation->slug}/indeks")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('ulang123', $regulation->fresh()->rag_doc_id);
        Http::assertSent(fn (Request $request) => Str::endsWith($request->url(), '/rag/ingest/pdf'));

        // Berkasnya hilang dari disk: jangan 500, beri tahu admin untuk unggah ulang.
        Storage::disk('public')->delete($regulation->file_path);

        $this->actingAs($this->admin())
            ->post("/admin/kebijakan/{$regulation->slug}/indeks")
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_kategori_disamakan_huruf_besar_kecilnya(): void
    {
        Storage::fake('public');
        $this->fakeRag();

        $this->actingAs($this->admin())->post('/admin/kebijakan', $this->payload([
            'category' => '  perizinan USAHA ',
            'status' => 'draft',
        ]))->assertRedirect();

        $this->assertSame('Perizinan usaha', Regulation::firstOrFail()->category);
    }

    public function test_daftar_kebijakan_admin_bisa_dicari_dan_dipaginasi(): void
    {
        foreach (range(1, 12) as $n) {
            Regulation::create(['title' => "Kebijakan Nomor {$n}", 'slug' => "kebijakan-{$n}", 'status' => 'draft']);
        }

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/kebijakan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Regulations')
                ->has('regulations.data', 10)
                ->where('regulations.total', 12));

        $this->actingAs($admin)->get('/admin/kebijakan?q=Nomor 7')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('regulations.data', 1)
                ->where('regulations.data.0.title', 'Kebijakan Nomor 7'));
    }

    public function test_judul_sama_menghasilkan_slug_berbeda(): void
    {
        Storage::fake('public');
        $this->fakeRag();

        $admin = $this->admin();
        $payload = $this->payload(['status' => 'draft', 'title' => 'Kewajiban Data Pribadi']);

        $this->actingAs($admin)->post('/admin/kebijakan', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/kebijakan', $payload + [
            'file' => UploadedFile::fake()->create('kedua.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(
            ['kewajiban-data-pribadi', 'kewajiban-data-pribadi-2'],
            Regulation::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_mengubah_tanpa_berkas_baru_mempertahankan_indeks_lama(): void
    {
        Storage::fake('public');
        $this->fakeRag('lama1');

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload(['status' => 'draft']))->assertRedirect();

        $regulation = Regulation::firstOrFail();

        $this->actingAs($admin)->patch("/admin/kebijakan/{$regulation->slug}", [
            'title' => 'Memilih Badan Usaha (Revisi)',
            'slug' => $regulation->slug,
            'status' => 'published',
        ])->assertRedirect();

        $regulation->refresh();
        $this->assertSame('Memilih Badan Usaha (Revisi)', $regulation->title);
        $this->assertSame('published', $regulation->status);
        $this->assertNotNull($regulation->published_at);
        $this->assertSame('lama1', $regulation->rag_doc_id);
    }

    public function test_menghapus_kebijakan_membuang_pdf_dan_rujukannya(): void
    {
        Storage::fake('public');
        $this->fakeRag('hapus1');

        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/kebijakan', $this->payload())->assertRedirect();

        $regulation = Regulation::firstOrFail();
        $path = $regulation->file_path;

        $this->actingAs($admin)->delete("/admin/kebijakan/{$regulation->slug}")->assertRedirect();

        $this->assertDatabaseCount('regulations', 0);
        Storage::disk('public')->assertMissing($path);
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && Str::endsWith($request->url(), '/rag/documents/hapus1'));
    }
}
