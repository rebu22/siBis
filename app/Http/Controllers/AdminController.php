<?php

namespace App\Http\Controllers;

use App\Models\AnswerReport;
use App\Models\Article;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UC-03 + area admin: dasbor, gerbang OTP, monitoring pengguna, dan CRUD artikel.
 * Yang belum dibangun: unggah PDF ke basis pengetahuan, monitoring pembayaran, laporan bug.
 */
class AdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'chats' => ChatSession::query()->count(),
                'messages' => ChatMessage::query()->count(),
                'reports_open' => AnswerReport::query()->where('status', 'baru')->count(),
                'articles' => Article::query()->count(),
            ],
            'reports' => AnswerReport::query()->with('message:id,body')->latest('id')->take(10)->get(),
        ]);
    }

    public function showTwoFactor(Request $request): Response
    {
        return Inertia::render('Admin/TwoFactor', [
            'verified_at' => $request->user()->two_factor_at?->toDateTimeString(),
            'masked_email' => $this->mask((string) $request->user()->email),
        ]);
    }

    /** UC-03: kirim kode sekali pakai. Kode lama langsung tidak berlaku. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->otpCodes()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->otpCodes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(OtpCode::TTL_SECONDS),
        ]);

        // MAIL_MAILER=log saat dev, jadi kode muncul di storage/logs/laravel.log.
        Mail::raw(
            "Kode verifikasi admin siBis: {$code}\n\nBerlaku ".intdiv(OtpCode::TTL_SECONDS, 60).' menit. Jangan bagikan kode ini.',
            fn ($message) => $message->to($user->email)->subject('Kode verifikasi admin siBis'),
        );

        return back()->with('success', 'Kode verifikasi dikirim ke email admin.');
    }

    /** UC-03: maks 5 percobaan per kode, kode disimpan sebagai hash. */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $request->user();

        $otp = $user->otpCodes()->whereNull('consumed_at')->latest('id')->first();

        if ($otp === null || ! $otp->isUsable()) {
            return back()->with('error', 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru.');
        }

        $otp->increment('attempts');

        if (! Hash::check($request->string('code')->toString(), $otp->code_hash)) {
            $remaining = max(0, OtpCode::MAX_ATTEMPTS - $otp->attempts);

            return back()->with('error', "Kode salah. Sisa percobaan: {$remaining}.");
        }

        $otp->update(['consumed_at' => now()]);
        $user->forceFill(['two_factor_at' => now()])->save();

        return redirect()->route('admin.dashboard')->with('success', 'Verifikasi dua faktor berhasil.');
    }

    /** RF-18: monitoring pengguna — paket, pemakaian kuota hari ini, jumlah percakapan. */
    public function users(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->withCount('chatSessions')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => (string) $user->plan,
                'verified' => $user->email_verified_at?->toDateString(),
                'quota_used' => $user->quota_date?->isToday() ? $user->quota_used : 0,
                'quota_limit' => $user->dailyQuota(),
                'sessions' => $user->chat_sessions_count,
                'joined' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'search' => $search,
            'summary' => [
                'total' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'premium' => User::query()->where('plan', 'premium')->count(),
                'asked_today' => (int) User::query()->whereDate('quota_date', now()->toDateString())->sum('quota_used'),
            ],
        ]);
    }

    /** RF-14: daftar artikel untuk CMS. Isi penuh ikut dikirim karena form ubah memakainya. */
    public function articles(): Response
    {
        $articles = Article::query()
            ->latest('id')
            ->take(60)
            ->get()
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->category,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'status' => $article->status,
                'published_at' => $article->published_at?->toDateString(),
                'updated_at' => $article->updated_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Articles', ['articles' => $articles]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $data = $this->validatedArticle($request);
        $data['user_id'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Article::create($data);

        return back()->with('success', 'Artikel tersimpan.');
    }

    public function updateArticle(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validatedArticle($request, $article);

        if ($data['status'] === 'published' && $article->published_at === null) {
            $data['published_at'] = now();
        }

        $article->update($data);

        return back()->with('success', 'Perubahan artikel tersimpan.');
    }

    public function destroyArticle(Article $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', 'Artikel dihapus.');
    }

    /**
     * Slug dibuat unik di sini, bukan lewat aturan unique, karena slug boleh dikosongkan
     * dan diambil dari judul. Hasilnya: "judul-sama", "judul-sama-2", dst.
     */
    private function validatedArticle(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash'],
            'category' => ['nullable', 'string', 'max:60'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:40', 'max:20000'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $base = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $base = $base !== '' ? $base : 'artikel';
        $slug = $base;
        $suffix = 2;

        while (Article::query()
            ->when($article !== null, fn ($query) => $query->whereKeyNot($article->id))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $data['slug'] = $slug;

        return $data;
    }

    /** Hanya tampilkan bentuk samar email di UI supaya alamat lengkap tidak dirender di halaman. */
    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::of($name)->substr(0, 2).'***@'.$domain;
    }
}
