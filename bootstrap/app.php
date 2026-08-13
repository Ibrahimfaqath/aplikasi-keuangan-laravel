<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // PENTING: Pengecualian Cookie UI agar Blade Server dapat membaca state tanpa dekripsi gagal
        $middleware->encryptCookies(except: [
            'theme',
            'privacy_mode',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();