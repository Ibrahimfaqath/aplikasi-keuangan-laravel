<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. Halaman depan: landing page (guest) atau redirect ke transaksi (sudah login)
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/transactions');
    }
    return view('landing');
});

// 2. Redirect URL /dashboard lama ke transactions.index
Route::get('/dashboard', fn () => redirect(Auth::check() ? route('transactions.index') : route('login')));

// 3. Route terproteksi Auth
Route::middleware('auth')->group(function () {
    // Export Laporan Keuangan
    Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
    Route::get('/transactions/export-excel', [TransactionController::class, 'exportExcel'])->name('transactions.export-excel');

    // Endpoint Parser Suara Lokal
    Route::post('/transactions/parse-voice', [TransactionController::class, 'parseVoice'])->name('transactions.parse-voice');

    // CRUD Utama Transaksi
    Route::resource('transactions', TransactionController::class);

    // Anggaran Bulanan
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';