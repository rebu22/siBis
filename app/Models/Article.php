<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RF-02/03/04. Isi artikel disimpan sebagai teks dan dirender apa adanya (auto-escape Vue),
 * jadi tidak ada jalur XSS lewat v-html. Kalau nanti butuh editor rich-text, sanitasi dulu.
 */
#[Fillable(['user_id', 'title', 'slug', 'category', 'excerpt', 'body', 'status', 'published_at'])]
class Article extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * RF-03: pencarian judul/isi. Semua nilai lewat binding — tidak pernah digabung ke string SQL.
     * ponytail: wildcard (%/_) tidak di-escape, jadi "%" cocok ke semua. Tidak berbahaya.
     * Ganti ke FULLTEXT (MySQL) + index kalau artikel sudah ribuan.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(fn (Builder $w) => $w
            ->where('title', 'like', $like)
            ->orWhere('excerpt', 'like', $like)
            ->orWhere('body', 'like', $like));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
