<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

// Route 1: Menampilkan halaman utama (daftar transaksi)
Route::get('/transactions', [TransactionController::class, 'index']);

// Route 2: BARU - Menampilkan halaman form tambah transaksi
Route::get('/transactions/create', [TransactionController::class, 'create']);

// Route 3: Memproses dan menyimpan data baru (Method POST)
Route::post('/transactions', [TransactionController::class, 'store']);

// Route 4: Menghapus data transaksi (Method DELETE)
Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

// Route 5: Halaman Form Edit Transaksi
Route::get('/transactions/{id}/edit', [TransactionController::class, 'edit']);

// Route 6: Memproses perubahan data (Method PUT)
Route::put('/transactions/{id}', [TransactionController::class, 'update']);