<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** RF-08 / UC-21. Laporan bug dari pengunjung maupun pengguna login. */
#[Fillable(['user_id', 'name', 'email', 'description', 'screenshot_path', 'status', 'admin_note'])]
class BugReport extends Model
{
    /** Status penanganan di CMS (RF-19): baru, sedang ditangani, selesai. */
    public const STATUSES = ['baru', 'proses', 'selesai'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Screenshot disajikan lewat symlink `public/storage`, bukan dibaca PHP. */
    public function screenshotUrl(): ?string
    {
        return $this->screenshot_path
            ? Storage::disk('public')->url($this->screenshot_path)
            : null;
    }
}
