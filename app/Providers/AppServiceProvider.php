<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Kustomisasi cPanel: docroot subdomain (keuangan.almahir.cloud) berada
        // DI LUAR folder project (laravel-keuangan/). Folder docroot berisi aset
        // publik (favicon, build/, storage/) + .htaccess khusus.
        //
        // Terdeteksi otomatis — file ini boleh (dan sebaiknya) sama persis di
        // lokal & server, sehingga tidak ada lagi "file sakral" yang dilarang
        // ditimpa saat deploy.
        $docroot = base_path('../keuangan.almahir.cloud');
        if (is_dir($docroot)) {
            $this->app->usePublicPath($docroot);

            // Arahkan disk "public" ke folder storage di docroot (pengganti
            // storage:link) — upload bukti transaksi tersimpan langsung di
            // folder yang bisa diakses web: keuangan.almahir.cloud/storage/...
            $this->app['config']->set('filesystems.disks.public.root', public_path('storage'));
            $this->app['config']->set('filesystems.disks.public.url', url('/storage'));
        }

        Paginator::useTailwind();
    }
}
