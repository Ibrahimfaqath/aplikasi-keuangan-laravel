<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        $this->configureProductionStorage();

        // Throttle per-user (bukan per-IP) agar satu IP ramai tidak membuat
        // user lain ikut kena 429, dan satu user nakal tidak bisa menghabiskan
        // kuota IP bersama. Fallback ke IP untuk tamu.
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Login demo publik: dibatasi per-IP supaya tidak bisa di-spam brute-force.
        RateLimiter::for('demo', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    /**
     * Produksi (cPanel): folder project dan docroot subdomain terpisah,
     * sehingga symlink `storage:link` tidak pernah melayani upload dari luar
     * project. Arahkan disk `public` langsung ke folder storage di docroot
     * agar bukti transaksi yang baru di-upload langsung tersaji di web
     * (https://finance.almahir.cloud/storage/...).
     *
     * Hanya aktif di produksi; lokal memakai disk default + storage:link.
     */
    private function configureProductionStorage(): void
    {
        if (config('app.env') !== 'production') {
            return;
        }

        // base_path() = <home>/laravel_finance  →  docroot = <home>/finance.almahir.cloud
        $docroot = dirname(base_path()).'/finance.almahir.cloud';
        $publicRoot = $docroot.'/storage';

        // Bukan layout cPanel standar? Biarkan disk default, jangan crash.
        if (! is_dir($publicRoot)) {
            return;
        }

        config([
            'filesystems.disks.public.root' => $publicRoot,
            'filesystems.disks.public.url' => rtrim(config('app.url'), '/').'/storage',
            'filesystems.disks.public.visibility' => 'public',
        ]);
    }
}
