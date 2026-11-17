<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jembatan Laravel -> FastAPI (NFR-23: jaringan privat + token internal).
 *
 * - NFR-16: PiiFilter meredaksi email/telepon/NIK/NPWP sebelum dikirim; pesan asli tetap utuh di DB.
 * - NFR-17: layanan mati/timeout => jawaban degraded, bukan exception. Pemanggil tidak memotong kuota.
 * - NFR-18/19: metrik durasi & status per panggilan (tanpa isi pertanyaan), plus alert bila lambat.
 *
 * kontrak FastAPI (ai-service/app.py):
 *   POST /rag/ask  {question, history:[{role,content}]}
 *   -> 200 {answer: string, sources: [{title, url?, snippet?}]}
 *   POST /rag/ingest/pdf (multipart file, title, url) -> {id, title, chunks}
 *   GET  /rag/documents -> [{id, title, chunks}]
 *   DELETE /rag/documents/{id} -> {deleted: int}
 */
class RagClient
{
    public const DEGRADED_ANSWER = 'Layanan konsultasi sementara tidak tersedia. Silakan coba beberapa saat lagi.';

    /** ponytail: model 3B di CPU lambat kalau prompt panjang. 4 giliran terakhir cukup untuk menyambung percakapan; naikkan kalau pindah ke model/host lebih cepat. */
    private const HISTORY_TURNS = 4;

    private const HISTORY_CHAR_LIMIT = 400;

    /** Unggah PDF jauh lebih lama dari tanya-jawab; batas ini milik endpoint ingest saja. */
    private const INGEST_TIMEOUT = 600;

    public function __construct(private Metrics $metrics) {}

    /** @param  iterable<int, ChatMessage>  $history  pesan sebelum pertanyaan baru
     *  @return array{answer: string, sources: array<int, array<string, mixed>>, degraded: bool} */
    public function ask(string $question, iterable $history = []): array
    {
        $mulai = microtime(true);

        $payload = [
            'question' => PiiFilter::redact($question),
            'history' => collect($history)
                ->map(fn (ChatMessage $message) => [
                    'role' => $message->role,
                    'content' => PiiFilter::redact(mb_substr($message->body, 0, self::HISTORY_CHAR_LIMIT)),
                ])
                ->slice(-self::HISTORY_TURNS)
                ->values()
                ->all(),
        ];

        try {
            $response = Http::withToken((string) config('services.rag.token'))
                ->acceptJson()
                ->timeout((int) config('services.rag.timeout'))
                ->post($this->url('/rag/ask'), $payload);

            $durasi = $this->durasi($mulai);

            if ($response->failed() || ! is_string($response->json('answer'))) {
                $this->metrics->record('rag.ask', [
                    'status' => 'error',
                    'http_status' => $response->status(),
                    'duration_ms' => $durasi,
                    'sources' => 0,
                ]);
                $this->alertLambat($durasi);

                return $this->degraded('respons tidak valid (HTTP '.$response->status().')');
            }

            $jawaban = $response->json('answer');
            $sumber = $this->sources($response->json('sources'));

            $this->metrics->record('rag.ask', [
                'status' => 'ok',
                'http_status' => $response->status(),
                'duration_ms' => $durasi,
                'sources' => count($sumber),
                'answer_chars' => mb_strlen($jawaban),
            ]);
            $this->alertLambat($durasi);

            return [
                'answer' => $jawaban,
                'sources' => $sumber,
                'degraded' => false,
            ];
        } catch (\Throwable $e) {
            $this->metrics->record('rag.ask', [
                'status' => 'exception',
                'duration_ms' => $this->durasi($mulai),
                'reason' => class_basename($e),
            ]);

            return $this->degraded($e->getMessage());
        }
    }

    /**
     * UC-18: unggah PDF regulasi ke basis pengetahuan. Teks diekstrak di layanan (pypdf),
     * karena PHP tidak punya pembaca PDF bawaan dan menambah parser di sini cuma duplikasi.
     *
     * @return array{id: string, title: string, chunks: int}
     *
     * @throws \RuntimeException saat layanan mati atau PDF tidak terbaca
     */
    public function ingestPdf(UploadedFile $file, string $title, string $url = ''): array
    {
        try {
            $response = Http::withToken((string) config('services.rag.token'))
                ->timeout(self::INGEST_TIMEOUT) // PDF besar butuh puluhan detik, beda dari tanya-jawab
                ->attach('file', (string) file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->url('/rag/ingest/pdf'), ['title' => $title, 'url' => $url]);
        } catch (\Throwable $e) {
            Log::warning('RAG ingest gagal', ['reason' => $e->getMessage()]);

            throw new \RuntimeException('Layanan AI tidak bisa dihubungi. Coba lagi setelah layanan hidup.');
        }

        if ($response->failed() || ! is_string($response->json('id'))) {
            Log::warning('RAG ingest ditolak', ['status' => $response->status()]);

            throw new \RuntimeException((string) ($response->json('detail') ?? 'PDF gagal diindeks.'));
        }

        return [
            'id' => (string) $response->json('id'),
            'title' => (string) $response->json('title'),
            'chunks' => (int) $response->json('chunks'),
        ];
    }

    /** UC-25: hapus dokumen + potongannya dari Chroma dan indeks kata kunci. */
    public function deleteDocument(string $id): bool
    {
        $id = (string) preg_replace('/\W/', '', $id);

        if ($id === '') {
            return false;
        }

        try {
            $ok = Http::withToken((string) config('services.rag.token'))
                ->timeout((int) config('services.rag.timeout'))
                ->delete($this->url('/rag/documents/'.$id))
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('RAG hapus dokumen gagal', ['reason' => $e->getMessage()]);

            return false;
        }

        return $ok;
    }

    private function durasi(float $mulai): int
    {
        return (int) round((microtime(true) - $mulai) * 1000);
    }

    private function alertLambat(int $durasi): void
    {
        $batas = (int) config('services.rag.slow_ms', 10000);

        if ($durasi >= $batas) {
            Log::warning('RAG lambat', ['duration_ms' => $durasi, 'threshold_ms' => $batas]);
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

    private function url(string $path): string
    {
        return rtrim((string) config('services.rag.url'), '/').$path;
    }
}
