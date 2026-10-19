<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jembatan Laravel -> FastAPI (NFR-23: jaringan privat + token internal).
 *
 * - NFR-16: yang dikirim ke LLM hanya teks pertanyaan + peran riwayat. Tanpa nama/email/profil usaha.
 * - NFR-17: layanan mati/timeout => jawaban degraded, bukan exception. Pemanggil tidak memotong kuota.
 * - NFR-19: log hanya alasan kegagalan, tidak pernah isi pertanyaan.
 *
 * kontrak FastAPI (service/app.py):
 *   POST /rag/ask  {question, history:[{role,content}]}
 *   -> 200 {answer: string, sources: [{title, url?, snippet?}]}
 */
class RagClient
{
    public const DEGRADED_ANSWER = 'Layanan konsultasi sementara tidak tersedia. Silakan coba beberapa saat lagi.';

    /** @param  iterable<int, ChatMessage>  $history  pesan sebelum pertanyaan baru
     *  @return array{answer: string, sources: array<int, array<string, mixed>>, degraded: bool} */
    public function ask(string $question, iterable $history = []): array
    {
        $payload = [
            'question' => $question,
            'history' => collect($history)
                ->map(fn (ChatMessage $message) => [
                    'role' => $message->role,
                    'content' => $message->body,
                ])
                ->values()
                ->all(),
        ];

        try {
            $response = Http::withToken((string) config('services.rag.token'))
                ->acceptJson()
                ->timeout((int) config('services.rag.timeout'))
                ->post(rtrim((string) config('services.rag.url'), '/').'/rag/ask', $payload);

            if ($response->failed() || ! is_string($response->json('answer'))) {
                return $this->degraded('respons tidak valid (HTTP '.$response->status().')');
            }

            return [
                'answer' => $response->json('answer'),
                'sources' => $this->sources($response->json('sources')),
                'degraded' => false,
            ];
        } catch (\Throwable $e) {
            return $this->degraded($e->getMessage());
        }
    }

    /** @return array{answer: string, sources: array<int, array<string, mixed>>, degraded: bool} */
    private function degraded(string $reason): array
    {
        Log::warning('RAG tidak tersedia', ['reason' => $reason]);

        return ['answer' => self::DEGRADED_ANSWER, 'sources' => [], 'degraded' => true];
    }

    /** Referensi dari layanan tidak pernah dipercaya mentah-mentah; ambil kunci yang dikenal saja. */
    private function sources(mixed $sources): array
    {
        return collect(is_array($sources) ? $sources : [])
            ->filter(fn ($source) => is_array($source) && filled($source['title'] ?? null))
            ->map(fn (array $source) => [
                'title' => (string) $source['title'],
                'url' => (string) ($source['url'] ?? ''),
                'snippet' => (string) ($source['snippet'] ?? ''),
            ])
            ->take(5)
            ->values()
            ->all();
    }
}
