<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identitas tamu: token acak di cookie 30 hari (bukan sesi 2 jam), supaya
 * riwayat dan kuota tetap terbaca saat pengunjung kembali.
 * Token hanya tersimpan sebagai atribut request + kolom `guest_token`.
 */
class GuestToken
{
    public const COOKIE = 'sibis_guest';

    private const DAYS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(self::COOKIE);

        if (! $this->valid($token)) {
            $token = Str::random(40);

            cookie()->queue(Cookie::create(
                self::COOKIE,
                $token,
                now()->addDays(self::DAYS)->getTimestamp(),
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        $request->attributes->set('guest_token', $token);

        return $next($request);
    }

    private function valid(mixed $token): bool
    {
        return is_string($token) && preg_match('/^[A-Za-z0-9]{40}$/', $token) === 1;
    }
}
