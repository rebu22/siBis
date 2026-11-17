<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Di lokal: gagalkan (bukan diam-diam buang) atribut yang tidak fillable,
        // supaya bug sepeti role/email_verified_at yang "hilang" langsung terlihat.
        Model::preventSilentlyDiscardingAttributes($this->app->environment('local'));

        // Lapisan kedua selain kuota harian: batasi burst ke FastAPI (10 tanya/menit/user).
        RateLimiter::for('consult', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}
