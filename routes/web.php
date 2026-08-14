<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// 1. Redirect halaman depan: langsung ke transaksi (sudah login) atau login (guest)
//    — memangkas redirect chain agar halaman pertama lebih cepat.
Route::get('/', function () {
    return redirect(auth()->check() ? '/transactions' : '/login');
});

// 2. Halaman utama sudah menampilkan ringkasan keuangan (transactions.index),
//    jadi URL /dashboard lama diarahkan ke sana (jaga bookmark lama).
//    Guest langsung ke login — konsisten dengan halaman depan.
Route::get('/dashboard', fn () => redirect(auth()->check() ? route('transactions.index') : route('login')));

// 3. Route terproteksi Auth
Route::middleware('auth')->group(function () {
    // Export Laporan Keuangan
    Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
    Route::get('/transactions/export-excel', [TransactionController::class, 'exportExcel'])->name('transactions.export-excel');

    // CRUD Utama Transaksi
    Route::resource('transactions', TransactionController::class);

    // Anggaran Bulanan
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // AI Assistant
    Route::get('/ai', [AiController::class, 'page'])->name('ai.index');
    Route::post('/ai/chat', [AiController::class, 'chat'])->name('ai.chat');
    Route::post('/ai/ocr', [AiController::class, 'ocr'])->name('ai.ocr');
    Route::delete('/ai/history', [AiController::class, 'clear'])->name('ai.clear');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';