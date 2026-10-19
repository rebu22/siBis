<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** RF-02/03/04: daftar, pencarian, dan detail artikel. */
class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        // RF-03: kata kunci lewat scopeSearch (binding, tidak pernah dirangkai ke string SQL).
        // `only` dipakai supaya query string liar tidak ikut diteruskan balik ke props.
        $filters = $request->only('q', 'kategori');

        return Inertia::render('Articles/Index', [
            'articles' => Article::query()
                ->published()
                ->search($filters['q'] ?? null)
                ->when(filled($filters['kategori'] ?? null), fn ($query) => $query->where('category', $filters['kategori']))
                ->latest('published_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Article $article) => [
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'category' => $article->category,
                    'excerpt' => $article->excerpt,
                    'published_at' => $article->published_at?->toDateString(),
                ]),
            'filters' => $filters,
            'categories' => Article::query()->published()->whereNotNull('category')
                ->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function show(Article $article): Response
    {
        abort_unless($article->status === 'published' && $article->published_at, 404);

        return Inertia::render('Articles/Show', [
            'article' => [
                'slug' => $article->slug,
                'title' => $article->title,
                'category' => $article->category,
                'body' => $article->body,
                'published_at' => $article->published_at?->toDateString(),
                'author' => $article->author?->name,
            ],
        ]);
    }
}
