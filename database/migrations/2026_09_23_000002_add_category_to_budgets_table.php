<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Rata: satu anggaran keseluruhan per (user, bulan, tahun).
            // Jadi composite key baru berbasis kategori; index lama di-drop
            // agar tidak melarang dua baris (mis. keseluruhan + per kategori)
            // pada user + bulan + tahun yang sama.
            $table->dropUnique(['user_id', 'month', 'year']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            // '' = anggaran keseluruhan (semua pengeluaran), selain itu = nama kategori.
            $table->string('category', 50)->default('')->after('year');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['user_id', 'month', 'year', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'month', 'year', 'category']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['user_id', 'month', 'year']);
        });
    }
};
