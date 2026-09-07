<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

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
        // Pesan ramah saat kena throttle (429) di route AI/chat.
        // Default Laravel bahasa Inggris ("Too Many Attempts."), kita ubah
        // jadi Indonesia + kasih tau sisa detik tunggu (Retry-After).
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson() && $request->is('ai/*', 'transactions/parse-voice')) {
                $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);

                return response()->json([
                    'reply' => "Sabar ya, kamu terlalu cepat! Tunggu {$retryAfter} detik lagi baru coba lagi ⏳",
                    'message' => "Terlalu banyak permintaan. Coba lagi dalam {$retryAfter} detik.",
                    'retry_after' => $retryAfter,
                ], 429, ['Retry-After' => $retryAfter]);
            }

            return null;
        });
    })->create();
