<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;    protected $fillable = [
        'user_id', 'title', 'category', 'amount', 'type', 'transaction_date', 'image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /** Kategori pemasukan yang tersedia. */
    public const INCOME_CATEGORIES = [
        'Gaji',
        'Bonus',
        'Bisnis',
        'Investasi',
        'Hadiah',
        'Lainnya',
    ];

    /** Kategori pengeluaran yang tersedia. */
    public const EXPENSE_CATEGORIES = [
        'Makanan & Minuman',
        'Transportasi',
        'Tagihan & Utilitas',
        'Belanja',
        'Hiburan',
        'Kesehatan',
        'Pendidikan',
        'Keluarga',
        'Lainnya',
    ];

    /**
     * Daftar kategori untuk sebuah jenis transaksi.
     */
    public static function categoriesFor(string $type): array
    {
        return $type === 'income' ? self::INCOME_CATEGORIES : self::EXPENSE_CATEGORIES;
    }

    /**
     * Semua kategori (pemasukan + pengeluaran), unik & urut.
     */
    public static function allCategories(): array
    {
        return array_values(array_unique(array_merge(self::INCOME_CATEGORIES, self::EXPENSE_CATEGORIES)));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
