<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * NFR-10/NFR-25: header keamanan dasar untuk semua respons web.
 * CSP sengaja di-skip saat dev server Vite hidup (butuh eval/HMR dari port 5173),
 * produksi selalu dapat CSP ketat.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce harus dibuat SEBELUM view dirender: @vite dan @routes menempelkan
        // nonce ini ke script inline-nya, jadi script-src tidak perlu 'unsafe-inline'.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        if (! (app()->environment('local') && file_exists(public_path('hot')))) {
            $headers['Content-Security-Policy'] = "default-src 'self'; img-src 'self' data:; "
                ."style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-{$nonce}'; connect-src 'self'; "
                ."frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
        }

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        $response->headers->add($headers);

        return $response;
    }
}
