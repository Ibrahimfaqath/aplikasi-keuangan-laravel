<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Dipakai oleh: filter tipe + rentang tanggal (statistik, tren, monthlyExpense)
            // dan filter kategori. Nama pendek agar aman di limit 64 char MySQL.
            $table->index(['user_id', 'type', 'transaction_date'], 'transactions_user_type_date_index');
            $table->index(['user_id', 'category'], 'transactions_user_category_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_type_date_index');
            $table->dropIndex('transactions_user_category_index');
        });
    }
};
