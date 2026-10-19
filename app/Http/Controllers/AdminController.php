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
 * UC-03 + dasbor admin. CMS penuh (CRUD artikel, unggah PDF) belum dibangun — halaman ini
 * hanya menampilkan ringkasan supaya area admin dan alur OTP bisa diuji end-to-end.
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

    /** Hanya tampilkan bentuk samar email di UI supaya alamat lengkap tidak dirender di halaman. */
    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::of($name)->substr(0, 2).'***@'.$domain;
    }
}
