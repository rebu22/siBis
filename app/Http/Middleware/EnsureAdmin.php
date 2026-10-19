<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * UC-03/NFR: area admin butuh role admin DAN OTP yang baru diverifikasi (30 menit).
 * Rute admin.2fa* dikecualikan supaya verifikasi OTP itu sendiri bisa diakses.
 */
class EnsureAdmin
{
    public const SESSION_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->isAdmin(), 403);

        $verified = $user->two_factor_at !== null
            && $user->two_factor_at->gt(now()->subMinutes(self::SESSION_MINUTES));

        if (! $verified && ! $request->routeIs('admin.2fa*')) {
            return redirect()->route('admin.2fa');
        }

        return $next($request);
    }
}
