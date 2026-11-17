<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** RF-02/03/04: daftar, pencarian, dan detail kebijakan (PDF). */
class RegulationController extends Controller
{
    public function index(Request $request): Response
    {
        // RF-03: kata kunci lewat scopeSearch (binding, tidak pernah dirangkai ke string SQL).
        // `only` dipakai supaya query string liar tidak ikut diteruskan balik ke props.
        $filters = $request->only('q', 'kategori');

        return Inertia::render('Kebijakan/Index', [
            'regulations' => Regulation::query()
                ->published()
                ->search($filters['q'] ?? null)
                ->when(filled($filters['kategori'] ?? null), fn ($query) => $query->where('category', $filters['kategori']))
                ->latest('published_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Regulation $regulation) => [
                    'slug' => $regulation->slug,
                    'title' => $regulation->title,
                    'category' => $regulation->category,
                    'excerpt' => $regulation->excerpt,
                    'size_kb' => (int) round($regulation->file_size / 1024),
                    'published_at' => $regulation->published_at?->toDateString(),
                ]),
            'filters' => $filters,
            'categories' => Regulation::query()->published()->whereNotNull('category')
                ->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function show(Regulation $regulation): Response
    {
        abort_unless($regulation->status === 'published' && $regulation->published_at, 404);

        return Inertia::render('Kebijakan/Show', [
            'regulation' => [
                'slug' => $regulation->slug,
                'title' => $regulation->title,
                'category' => $regulation->category,
                'excerpt' => $regulation->excerpt,
                'file_url' => $regulation->fileUrl(),
                'size_kb' => (int) round($regulation->file_size / 1024),
                'published_at' => $regulation->published_at?->toDateString(),
            ],
        ]);
    }
}
