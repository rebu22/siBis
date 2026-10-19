<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UC-03: OTP admin. Kode disimpan sebagai hash (bukan plaintext), sekali pakai, berbatas waktu.
 * Angka pastinya tidak ada di SRS — dipilih wajar: 6 digit, 5 menit, maks 5 percobaan.
 */
#[Fillable(['user_id', 'code_hash', 'expires_at', 'attempts', 'consumed_at'])]
class OtpCode extends Model
{
    public const TTL_SECONDS = 300;

    public const MAX_ATTEMPTS = 5;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->attempts < self::MAX_ATTEMPTS
            && $this->expires_at->isFuture();
    }
}
