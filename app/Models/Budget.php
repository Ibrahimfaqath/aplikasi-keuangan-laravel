<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;    protected $fillable = [
        'user_id', 'amount', 'month', 'year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}