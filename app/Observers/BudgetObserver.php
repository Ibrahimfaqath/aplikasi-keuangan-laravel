<?php

namespace App\Observers;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Model;

class BudgetObserver extends AuditObserver
{
    protected function label(Model $model): string
    {
        return $model instanceof Budget ? "Anggaran {$model->month}/{$model->year}" : 'Anggaran';
    }

    protected function detail(Model $model): string
    {
        $budget = $model instanceof Budget ? $model : null;
        $amount = 'Rp '.number_format((float) ($budget?->amount ?? 0), 0, ',', '.');

        return $amount.' untuk bulan '.($budget?->month ?? '-').'/'.($budget?->year ?? '-');
    }
}
