<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Demo Account
    |--------------------------------------------------------------------------
    |
    | Akun demo publik ("Coba Demo") untuk pengunjung mencoba aplikasi tanpa
    | membuat akun. Akun ini hanyalah user biasa — SEMUA query tetap di-scope
    | per user_id sehingga tidak pernah bisa mengakses data user lain.
    |
    | DEMO_MODE_ENABLED=false akan menonaktifkan tombol demo & read-only guard.
    | Password demo dipakai seeder saja (jangan pernah tampilkan di frontend);
    | ganti dengan nilai unik di .env produksi.
    |
    */

    'enabled' => (bool) env('DEMO_MODE_ENABLED', true),

    'email' => env('DEMO_ACCOUNT_EMAIL', 'demo@dompetku.app'),

    'name' => env('DEMO_ACCOUNT_NAME', 'Demo User'),

    'password' => env('DEMO_ACCOUNT_PASSWORD', 'demo2026!'),
];
