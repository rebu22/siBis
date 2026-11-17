<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * NFR-18/19/20: metrik ringan berbentuk baris JSON di channel `metrics`.
 * Hanya metadata (durasi, status, jumlah sumber) — tidak pernah isi pertanyaan.
 */
class Metrics
{
    /** @param array<string, mixed> $context */
    public function record(string $event, array $context = []): void
    {
        Log::channel('metrics')->info($event, $context + [
            'at' => now()->toIso8601String(),
        ]);
    }
}
