<?php

return [

    /*
     * NFR-24: backup harian, retensi 30 hari.
     * Disk `backups` berada di storage/app/backups (tidak bisa diakses publik).
     */
    'disk' => env('BACKUP_DISK', 'backups'),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

];
