<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** UC-11 / RF-26. Laporan jawaban tidak akurat, ditinjau admin lewat /admin/laporan. */
#[Fillable(['chat_message_id', 'user_id', 'guest_token', 'reason', 'note', 'status'])]
class AnswerReport extends Model
{
    /** Status penanganan di CMS (RF-19/RF-26): baru masuk, sedang ditinjau, selesai. */
    public const STATUSES = ['baru', 'proses', 'selesai'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    /** Pelapor; dipakai CMS supaya admin tahu siapa yang mengirim laporan. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
