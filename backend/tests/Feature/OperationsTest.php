<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Services\Metrics;
use App\Services\RagClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Fase D: operasional.
 * - NFR-18/19: metrik latensi & status panggilan LLM
 * - NFR-16: redaksi PII sebelum teks dikirim ke layanan AI
 * - NFR-20: health check + alert
 * Backup (NFR-24) diuji terpisah di BackupTest karena VACUUM INTO
 * tidak boleh berjalan di dalam transaksi RefreshDatabase.
 * Jalankan: php artisan test --filter=Operations
 */
class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pertanyaan_dan_riwayat_diredaksi_sebelum_dikirim_ke_rag(): void
    {
        Http::fake([
            '*/rag/ask' => Http::response([
                'answer' => 'Jawaban dengan rujukan.',
                'sources' => [['title' => 'UU PT']],
            ]),
        ]);

        $metrics = Mockery::mock(Metrics::class);
        $metrics->shouldReceive('record')->once()->with('rag.ask', Mockery::on(
            fn (array $context) => $context['status'] === 'ok'
                && $context['sources'] === 1
                && $context['duration_ms'] >= 0
        ));
        $this->app->instance(Metrics::class, $metrics);

        $history = [new ChatMessage([
            'role' => 'user',
            'body' => 'Email saya budi@example.com',
        ])];

        $hasil = $this->app->make(RagClient::class)->ask(
            'Hubungi 081234567890, NIK 3174012509900001, NPWP 12.345.678.9-012.345',
            $history
        );

        $this->assertFalse($hasil['degraded']);
        $this->assertSame('Jawaban dengan rujukan.', $hasil['answer']);

        Http::assertSent(function (Request $request) {
            return $request['question'] === 'Hubungi [telepon], NIK [nik], NPWP [npwp]'
                && $request['history'][0]['content'] === 'Email saya [email]';
        });
    }

    public function test_kegagalan_rag_dicatat_sebagai_metrik_error_dan_jawaban_degraded(): void
    {
        Http::fake([
            '*/rag/ask' => Http::response(['detail' => 'down'], 503),
        ]);

        $metrics = Mockery::mock(Metrics::class);
        $metrics->shouldReceive('record')->once()->with('rag.ask', Mockery::on(
            fn (array $context) => $context['status'] === 'error'
                && $context['http_status'] === 503
        ));
        $this->app->instance(Metrics::class, $metrics);

        $hasil = $this->app->make(RagClient::class)->ask('Apa itu PT Perorangan?');

        $this->assertTrue($hasil['degraded']);
        $this->assertSame(RagClient::DEGRADED_ANSWER, $hasil['answer']);
    }

    public function test_health_check_lolos_saat_semua_layanan_hidup(): void
    {
        Http::fake(['*/health' => Http::response(['status' => 'ok'])]);

        $this->artisan('sibis:health')->assertSuccessful();
    }

    public function test_health_check_gagal_saat_rag_tidak_sehat(): void
    {
        Http::fake(['*/health' => Http::response('down', 503)]);

        $this->artisan('sibis:health')->assertExitCode(1);
    }
}
