<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Kebijakan/regulasi berbentuk PDF. Isi teksnya tidak disimpan di sini: PDF diunggah ke layanan RAG,
 * yang mengekstrak dan memotongnya. `rag_doc_id` adalah kunci untuk menghapusnya kembali dari sana.
 */
#[Fillable(['user_id', 'title', 'slug', 'category', 'excerpt', 'file_path', 'file_size', 'rag_doc_id', 'status', 'published_at'])]
class Regulation extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Yang tampil ke publik hanya kebijakan yang benar-benar punya PDF. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->whereNotNull('file_path');
    }

    /**
     * RF-03: pencarian judul/ringkasan. Semua nilai lewat binding — tidak pernah digabung ke string SQL.
     * ponytail: wildcard (%/_) tidak di-escape, jadi "%" cocok ke semua. Tidak berbahaya.
     * Ganti ke FULLTEXT (MySQL) + index kalau kebijakan sudah ribuan.
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
            ->orWhere('excerpt', 'like', $like));
    }

    /** PDF disajikan lewat symlink `public/storage`, bukan dibaca PHP (NFR: hemat memori). */
    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
