<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--keep=30 : Berapa banyak backup terakhir yang dipertahankan (0 = hapus semua setelah backup)}
        {--force : Lewati peringatan saat berjalan di environment non-produksi}';

    protected $description = 'Buat backup lengkap database ke storage/app/backups';

    public function handle(DatabaseBackupService $service): int
    {
        $keep = (int) $this->option('keep');

        try {
            $backup = $service->take($keep);
        } catch (Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $size = number_format($backup['size'] / 1024, 1, ',', '.').' kB';

        $this->info('Backup selesai: '.$backup['filename'].' ('.$size.')');
        $this->comment('Tersimpan di storage/app/backups — simpan salinan di luar server untuk keamanan berlapis.');

        return self::SUCCESS;
    }
}
