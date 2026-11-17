<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/** NFR-24: dijalankan scheduler harian; juga bisa manual `php artisan sibis:backup`. */
class BackupDatabase extends Command
{
    protected $signature = 'sibis:backup';

    protected $description = 'Buat backup database dan hapus backup lebih lama dari masa retensi';

    public function handle(BackupService $backup): int
    {
        try {
            $file = $backup->create();
        } catch (Throwable $e) {
            Log::error('Backup database gagal', ['reason' => $e->getMessage()]);
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $dihapus = $backup->prune();

        Log::info('Backup database selesai', ['file' => $file, 'pruned' => $dihapus]);
        $this->info("Backup tersimpan: {$file} (dihapus: {$dihapus})");

        return self::SUCCESS;
    }
}
