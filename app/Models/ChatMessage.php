<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu pesan dalam percakapan; `sources` = blok referensi regulasi (UC-07 langkah 2.4). */
#[Fillable(['chat_session_id', 'role', 'body', 'sources', 'degraded'])]
class ChatMessage extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'degraded' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(AnswerRating::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AnswerReport::class);
    }
}
