<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class TransactionObserver extends AuditObserver
{
    protected function label(Model $model): string
    {
        return 'Transaksi';
    }

    protected function detail(Model $model): string
    {
        $transaction = $model instanceof Transaction ? $model : null;
        $title = $transaction?->title ?: 'tanpa judul';
        $type = $transaction?->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $amount = 'Rp '.number_format((float) ($transaction?->amount ?? 0), 0, ',', '.');
        $category = $transaction?->category ?: '-';

        return "{$title} — {$amount} ({$type}, kategori: {$category})";
    }
}
