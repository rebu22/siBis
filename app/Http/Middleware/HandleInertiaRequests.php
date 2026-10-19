<?php

namespace App\Http\Middleware;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Daftar putih: jangan kirim kolom sensitif (quota, two_factor_at) ke klien.
                'user' => $request->user()?->only('id', 'name', 'email', 'role', 'plan', 'email_verified_at'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Footer/hero butuh kontak & profil di semua halaman publik (RF-01).
            'company' => fn () => CompanyProfile::current(),
        ];
    }
}
