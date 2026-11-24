<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use Illuminate\Console\Command;

/** Retensi data tamu: percakapan tanpa akun dihapus setelah 30 hari (UU PDP). */
class PurgeGuestData extends Command
{
    protected $signature = 'sibis:purge-guests';

    protected $description = 'Hapus percakapan tamu yang lebih tua dari 30 hari';

    public function handle(): int
    {
        $deleted = ChatSession::query()
            ->whereNull('user_id')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Percakapan tamu dihapus: {$deleted}.");

        return self::SUCCESS;
    }
}
