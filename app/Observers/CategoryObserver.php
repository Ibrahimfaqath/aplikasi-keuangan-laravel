<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryObserver extends AuditObserver
{
    protected function label(Model $model): string
    {
        return 'Kategori';
    }

    protected function detail(Model $model): string
    {
        $category = $model instanceof Category ? $model : null;
        $type = $category?->type === 'income' ? 'Pemasukan' : 'Pengeluaran';

        return ($category?->name ?? 'tanpa nama')." ({$type})";
    }
}
