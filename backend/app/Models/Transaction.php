<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RF-07/RF-21. Satu percobaan pembayaran premium lewat Midtrans. */
#[Fillable([
    'user_id', 'order_id', 'plan', 'amount', 'days', 'status', 'payment_type',
    'snap_token', 'snap_redirect_url', 'paid_at', 'expires_at', 'raw_response',
])]
class Transaction extends Model
{
    /** Status yang dikenal UI; nilai lain dari Midtrans tetap disimpan apa adanya. */
    public const STATUSES = ['pending', 'settlement', 'expire', 'cancel', 'deny', 'failure', 'refund'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /** Label status untuk tampilan; nilai tak dikenal ditampilkan apa adanya. */
    public static function label(string $status): string
    {
        return [
            'pending' => 'Menunggu pembayaran',
            'settlement' => 'Lunas',
            'capture' => 'Lunas',
            'expire' => 'Kedaluwarsa',
            'cancel' => 'Dibatalkan',
            'deny' => 'Ditolak',
            'failure' => 'Gagal',
            'refund' => 'Dikembalikan',
        ][$status] ?? $status;
    }
}
