<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** NFR-21: catat perubahan konten (siapa, kapan, apa). Tanpa data sensitif di `meta`. */
#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'meta'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Dipakai controller supaya pemanggilan konsisten dan tidak mengirim data sensitif. */
    public static function record(string $action, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }
}
