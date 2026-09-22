<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Benih kategori bawaan (global, milik semua user) yang diambil dari
     * konstanta Transaction. Idempotent: insertOrIgnore agar re-run tidak
     * menduplikasi baris.
     */
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (Transaction::INCOME_CATEGORIES as $name) {
            $rows[] = [
                'user_id' => null,
                'name' => $name,
                'type' => 'income',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (Transaction::EXPENSE_CATEGORIES as $name) {
            $rows[] = [
                'user_id' => null,
                'name' => $name,
                'type' => 'expense',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Category::query()->insertOrIgnore($rows);
    }
}
