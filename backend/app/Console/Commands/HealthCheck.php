<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * NFR-20: pemeriksaan kesehatan dasar + jalur alerting.
 * Kegagalan dicatat `critical`; arahkan LOG_STACK ke Slack/email bila perlu.
 */
class HealthCheck extends Command
{
    protected $signature = 'sibis:health';

    protected $description = 'Periksa database dan layanan RAG, catat alert bila tidak sehat';

    public function handle(): int
    {
        $sehat = true;

        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            $sehat = false;
            Log::critical('Database tidak sehat', ['reason' => $e->getMessage()]);
            $this->error('Database: gagal');
        }

        $url = rtrim((string) config('services.rag.url'), '/').'/health';

        try {
            $response = Http::timeout(5)->get($url);

            if (! $response->successful()) {
                $sehat = false;
                Log::critical('Layanan RAG tidak sehat', ['status' => $response->status(), 'url' => $url]);
                $this->error('RAG: HTTP '.$response->status());
            }
        } catch (Throwable $e) {
            $sehat = false;
            Log::critical('Layanan RAG tidak bisa dihubungi', ['reason' => $e->getMessage(), 'url' => $url]);
            $this->error('RAG: tidak bisa dihubungi');
        }

        $this->info($sehat ? 'Semua layanan sehat.' : 'Ada layanan bermasalah; lihat log.');

        return $sehat ? self::SUCCESS : self::FAILURE;
    }
}
