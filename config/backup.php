<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pencadangan Database (khusus pemilik)
    |--------------------------------------------------------------------------
    |
    | Backup adalah tanggung jawab infrastruktur — bukan fitur untuk user biasa.
    | Halaman /backups (lihat & unduh file .sql) hanya bisa diakses oleh akun
    | pemilik aplikasi (email di bawah). Kalau dikosongkan, halaman web
    | nonaktif total (404) dan backup hanya berjalan otomatis via cron.
    |
    */

    'owner_email' => env('BACKUP_OWNER_EMAIL', ''),
];
