<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. Halaman depan: landing page
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/transactions');
    }

    return view('landing');
});

// 2. Redirect /dashboard lama ke transactions.index
Route::get('/dashboard', fn () => redirect(Auth::check() ? route('transactions.index') : route('login')));

// 3. Route terproteksi Auth
Route::middleware('auth')->group(function () {
    // Export Laporan + parser suara: dibatasi juga agar tidak bisa di-spam.
    // Limiter "exports" = 30/menit per-user (lihat AppServiceProvider).
    Route::middleware('throttle:exports')->group(function () {
        Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
        Route::get('/transactions/export-excel', [TransactionController::class, 'exportExcel'])->name('transactions.export-excel');
        Route::post('/transactions/parse-voice', [TransactionController::class, 'parseVoice'])->name('transactions.parse-voice');
    });

    // Tren dashboard lazy-load (1 query ringan per klik tab).
    // Ditaruh SEBELUM resource agar tidak tertelan route resource.
    // Sengaja tanpa throttle "exports" agar klik tab grafik tidak memakan kuota export.
    Route::get('/transactions/trend', [TransactionController::class, 'trend'])->name('transactions.trend');

    // CRUD Utama Transaksi (tanpa show yang tidak ada controller-nya)
    Route::resource('transactions', TransactionController::class)->except(['show']);

    // Anggaran Bulanan
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI Assistant Routes
    Route::get('/ai', [AiController::class, 'page'])->name('ai.index');
    Route::delete('/ai/history', [AiController::class, 'clear'])->name('ai.clear');

    // Route AI yang mahal (panggil API luar / tulis DB) — dibatasi 30x/menit per user
    // (limiter "ai", lihat AppServiceProvider) biar tidak bisa di-spam dan
    // jebol kuota. Lebih dari itu Laravel balas 429.
    Route::middleware('throttle:ai')->group(function () {
        Route::post('/ai/chat', [AiController::class, 'chat'])->name('ai.chat');
        Route::post('/ai/confirm', [AiController::class, 'confirmTransaction'])->name('ai.confirm');
        Route::post('/ai/cancel', [AiController::class, 'cancelTransaction'])->name('ai.cancel');
        Route::post('/ai/transactions', [AiController::class, 'storeTransactions'])->name('ai.transactions');
    });
});

require __DIR__.'/auth.php';
