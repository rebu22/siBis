<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** UC-11 / RF-26. Laporan jawaban tidak akurat, ditinjau admin (fitur CMS, belum dibangun). */
#[Fillable(['chat_message_id', 'user_id', 'reason', 'note', 'status'])]
class AnswerReport extends Model
{
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    /** Alasan yang boleh dipilih di UI (validasi rule `in:` memakai daftar ini). */
    public const REASONS = [
        'salah_pasal',
        'tidak_relevan',
        'tidak_ada_sumber',
        'usang',
        'lainnya',
    ];
}
