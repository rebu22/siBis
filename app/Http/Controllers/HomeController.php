<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Inertia\Inertia;
use Inertia\Response;

/** RF-01: halaman publik berisi profil perusahaan + artikel terbaru. */
class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home', [
            'articles' => Article::query()->published()->latest('published_at')->take(3)->get(),
        ]);
    }
}
