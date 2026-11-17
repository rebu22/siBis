<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Inertia\Inertia;
use Inertia\Response;

/** RF-01: halaman publik berisi profil perusahaan + kebijakan terbaru. */
class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home', [
            'regulations' => Regulation::query()->published()->latest('published_at')->take(3)->get(),
            'premium' => [
                'price' => (int) config('services.midtrans.premium_price'),
                'days' => (int) config('services.midtrans.premium_days'),
            ],
        ]);
    }
}
