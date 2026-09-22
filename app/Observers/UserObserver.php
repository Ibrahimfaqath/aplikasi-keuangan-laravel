<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserObserver extends AuditObserver
{
    protected array $excludedColumns = ['password', 'remember_token', 'updated_at', 'deleted_at'];

    protected function label(Model $model): string
    {
        return 'Profil';
    }

    protected function detail(Model $model): string
    {
        $user = $model instanceof User ? $model : null;

        return ($user?->name ?: 'tanpa nama').' <'.($user?->email ?: '-').'>';
    }
}
