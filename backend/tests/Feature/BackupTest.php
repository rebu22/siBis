<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * NFR-24: backup harian + retensi 30 hari.
 * Sengaja tanpa RefreshDatabase: VACUUM INTO tidak boleh berjalan di dalam
 * transaksi, dan snapshot SQLite tetap valid walau database uji kosong.
 * Jalankan: php artisan test --filter=Backup
 */
class BackupTest extends TestCase
{
    public function test_backup_membuat_snapshot_dan_menghapus_yang_kedaluwarsa(): void
    {
        Storage::fake('backups');

        $this->artisan('sibis:backup')->assertSuccessful();

        $files = Storage::disk('backups')->files();
        $this->assertCount(1, $files);

        $isi = gzdecode(Storage::disk('backups')->get($files[0]));
        $this->assertIsString($isi);
        $this->assertStringStartsWith('SQLite format 3', $isi);

        // Backup lama (31 hari) dihapus; berkas lain dibiarkan.
        Storage::disk('backups')->put('sibis-20000101-000000-sqlite.gz', 'lama');
        touch(
            Storage::disk('backups')->path('sibis-20000101-000000-sqlite.gz'),
            now()->subDays(31)->getTimestamp()
        );

        Storage::disk('backups')->put('catatan.txt', 'bukan backup');
        touch(
            Storage::disk('backups')->path('catatan.txt'),
            now()->subDays(90)->getTimestamp()
        );

        $this->artisan('sibis:backup')->assertSuccessful();

        Storage::disk('backups')->assertMissing('sibis-20000101-000000-sqlite.gz');
        Storage::disk('backups')->assertExists('catatan.txt');
    }
}
