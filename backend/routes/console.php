<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NFR-24: backup harian + retensi 30 hari (lihat config/backup.php).
Schedule::command('sibis:backup')
    ->dailyAt('02:30')
    ->withoutOverlapping();

// NFR-20: cek database & layanan RAG; kegagalan dicatat critical.
Schedule::command('sibis:health')
    ->everyFiveMinutes()
    ->withoutOverlapping();
