<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Scheduling
|--------------------------------------------------------------------------
|
| Jadwal berjalan lewat satu cron: `* * * * * php artisan schedule:run`.
| Pasang di cPanel menu "Cron Jobs". Detail di DEPLOY-CPANEL.md.
*/

// Backup database tiap hari 03:00 WIB. Retensi (30 terakhir — cukup ~1 bulan)
// otomatis dirapikan di dalam BackupDatabaseService::take().
Schedule::command('backup:database')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('[backup] backup harian selesai.'))
    ->onFailure(fn () => Log::error('[backup] backup harian GAGAL. Cek storage/logs/laravel.log.'));
