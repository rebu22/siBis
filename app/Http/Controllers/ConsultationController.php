<?php

namespace App\Http\Controllers;

use App\Models\AnswerReport;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\RagClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** UC-07..UC-11 / RF-05..RF-13, RF-25, RF-26: konsultasi AI, riwayat, penilaian, laporan. */
class ConsultationController extends Controller
{
    /** Berapa pesan sebelumnya yang dikirim sebagai konteks ke FastAPI (batasi biaya token). */
    private const HISTORY_TURNS = 8;

    /** UC-08 / RF-06: selalu lanjut sesi terakhir; riwayat lengkap ada di halaman terpisah. */
    public function index(Request $request): Response
    {
        $session = $request->user()->chatSessions()->latest('id')->first();

        return Inertia::render('Consultation/Index', [
            'messages' => $session ? $this->thread($session, $request->user()->id) : [],
            'quota' => $this->quota($request),
        ]);
    }

    /** UC-07 langkah 2: simpan pertanyaan, panggil FastAPI, simpan jawaban + referensinya. */
    public function ask(Request $request, RagClient $rag): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        // NFR: kuota dihitung server-side; permintaan di luar kuota tidak dikirim ke LLM.
        if ($user->quotaRemaining() < 1) {
            return back()->with('error', 'Kuota konsultasi harian sudah habis. Coba lagi besok atau tingkatkan ke Premium.');
        }

        $session = $user->chatSessions()->latest('id')->first() ?? $user->chatSessions()->create([]);

        $history = $session->messages()->latest('id')->take(self::HISTORY_TURNS)->get()->reverse()->values();

        $session->messages()->create(['role' => 'user', 'body' => $data['question']]);

        $result = $rag->ask($data['question'], $history);

        $session->messages()->create([
            'role' => 'assistant',
            'body' => $result['answer'],
            'sources' => $result['sources'],
            'degraded' => $result['degraded'],
        ]);

        // NFR-17: jawaban degraded tidak menghabiskan kuota user.
        if (! $result['degraded']) {
            $user->consumeQuota();
        }

        $session->title ??= Str::limit($data['question'], 60);
        $session->save();

        return back();
    }

    /** UC-09 / RF-07: daftar percakapan milik user sendiri. */
    public function history(Request $request): Response
    {
        return Inertia::render('History/Index', [
            'sessions' => $request->user()->chatSessions()
                ->withCount('messages')
                ->latest('id')
                ->paginate(15)
                ->through(fn (ChatSession $session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'messages_count' => $session->messages_count,
                    'updated_at' => $session->updated_at?->toDateTimeString(),
                ]),
        ]);
    }

    /** UC-09: tampilan read-only. Sesi milik user lain => 404, bukan 403 (tidak membocorkan keberadaan data). */
    public function show(Request $request, int $session): Response
    {
        $owned = $request->user()->chatSessions()->findOrFail($session);

        return Inertia::render('History/Show', [
            'session' => ['id' => $owned->id, 'title' => $owned->title, 'created_at' => $owned->created_at?->toDateTimeString()],
            'messages' => $this->thread($owned, $request->user()->id),
        ]);
    }

    /** UC-10 / RF-25: satu nilai per jawaban; menilai ulang akan menimpa. */
    public function rate(Request $request, int $message): RedirectResponse
    {
        $target = $this->ownedMessage($request, $message);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $target->ratings()->updateOrCreate(['user_id' => $request->user()->id], $data);

        return back()->with('success', 'Terima kasih, penilaian Anda tersimpan.');
    }

    /** UC-11 / RF-26: laporan jawaban tidak akurat, ditinjau admin. */
    public function report(Request $request, int $message): RedirectResponse
    {
        $target = $this->ownedMessage($request, $message);

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', AnswerReport::REASONS)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $target->reports()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Laporan terkirim. Tim kami akan meninjau jawaban ini.');
    }

    /** Semua akses jawaban disaring lewat kepemilikan sesi (anti-IDOR). */
    private function ownedMessage(Request $request, int $message): ChatMessage
    {
        return ChatMessage::query()
            ->whereKey($message)
            ->whereHas('session', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();
    }

    /** @return array<int, array<string, mixed>> */
    private function thread(ChatSession $session, int $userId): array
    {
        $messages = $session->messages()->with('ratings')->get();
        $mine = fn (ChatMessage $message) => $message->ratings->firstWhere('user_id', $userId)?->score;

        return $messages->map(fn (ChatMessage $message) => [
            'id' => $message->id,
            'role' => $message->role,
            'body' => $message->body,
            'sources' => $message->sources ?? [],
            'degraded' => $message->degraded,
            'created_at' => $message->created_at?->toDateTimeString(),
            'my_score' => $message->role === 'assistant' ? $mine($message) : null,
        ])->all();
    }

    /** @return array{remaining: int, limit: int, plan: string} */
    private function quota(Request $request): array
    {
        $user = $request->user();

        return ['remaining' => $user->quotaRemaining(), 'limit' => $user->dailyQuota(), 'plan' => (string) $user->plan];
    }
}
