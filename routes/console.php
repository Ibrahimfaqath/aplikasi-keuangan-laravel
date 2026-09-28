<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Scheduling
|--------------------------------------------------------------------------
|
| Jadwal berjalan lewat satu cron cPanel:
| `* * * * * cd /path/ke/project && /usr/local/bin/php /path/ke/project/artisan schedule:run`
| `cd` wajib karena cPanel menjalankan cron dari home directory. Detail di DEPLOY-CPANEL.md.
*/

// Backup database tiap hari 03:00 WIB. Retensi (30 terakhir — cukup ~1 bulan)
// otomatis dirapikan di dalam BackupDatabaseService::take().
Schedule::command('backup:database')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('[backup] backup harian selesai.'))
    ->onFailure(fn () => Log::error('[backup] backup harian GAGAL. Cek storage/logs/laravel.log.'));
