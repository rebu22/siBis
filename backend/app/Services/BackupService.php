<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * NFR-24: snapshot database + pemangkasan backup lama.
 *
 * SQLite memakai `VACUUM INTO` supaya snapshot konsisten walau ada penulisan.
 * MySQL/MariaDB memakai mysqldump; password dikirim lewat env MYSQL_PWD,
 * bukan argumen command line (agar tidak terlihat di daftar proses).
 */
class BackupService
{
    public function create(): string
    {
        $driver = DB::connection()->getDriverName();

        $raw = match ($driver) {
            'sqlite' => $this->sqliteSnapshot(),
            'mysql', 'mariadb' => $this->mysqlDump(),
            default => throw new RuntimeException('Driver database '.$driver.' belum didukung backup otomatis.'),
        };

        $file = 'sibis-'.now()->format('Ymd-His').'-'.$driver.'.gz';

        Storage::disk(config('backup.disk'))->put($file, gzencode($raw, 9));

        return $file;
    }

    /** Hapus backup lebih tua dari retensi. Mengembalikan jumlah file terhapus. */
    public function prune(): int
    {
        $disk = Storage::disk(config('backup.disk'));
        $batas = now()->subDays((int) config('backup.retention_days'))->getTimestamp();
        $dihapus = 0;

        foreach ($disk->files() as $file) {
            if (! str_starts_with(basename($file), 'sibis-')) {
                continue;
            }

            if ($disk->lastModified($file) < $batas) {
                $disk->delete($file);
                $dihapus++;
            }
        }

        return $dihapus;
    }

    private function sqliteSnapshot(): string
    {
        $pdo = DB::connection()->getPdo();
        $tmp = tempnam(sys_get_temp_dir(), 'sibis-backup');

        if ($tmp === false) {
            throw new RuntimeException('Tidak bisa membuat berkas sementara untuk backup.');
        }

        // VACUUM INTO menolak berkas yang sudah ada.
        @unlink($tmp);

        try {
            $pdo->exec('VACUUM INTO '.$pdo->quote($tmp));
            $data = file_get_contents($tmp);
        } catch (Throwable $e) {
            throw new RuntimeException('Snapshot SQLite gagal: '.$e->getMessage());
        } finally {
            @unlink($tmp);
        }

        if ($data === false) {
            throw new RuntimeException('Snapshot SQLite tidak bisa dibaca.');
        }

        return $data;
    }

    private function mysqlDump(): string
    {
        $config = config('database.connections.'.config('database.default'));

        $process = new Process([
            (string) config('backup.mysqldump'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--skip-lock-tables',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            (string) ($config['database'] ?? ''),
        ], null, [
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysqldump gagal: '.trim($process->getErrorOutput()));
        }

        return $process->getOutput();
    }
}
