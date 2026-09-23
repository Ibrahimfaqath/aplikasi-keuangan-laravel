<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('budgets', function (Blueprint $table) use ($driver) {
            // MySQL (1553) menolak drop index unik yang dipakai foreign key.
            // Lepas dulu FK-nya, baru index unik (user_id, month, year) — nanti
            // di-re-add setelah composite key baru dibuat. SQLite tidak punya
            // pembatas ini, dan dropForeign-nya no-op.
            if ($driver !== 'sqlite') {
                $table->dropForeign(['user_id']);
            }
            $table->dropUnique(['user_id', 'month', 'year']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            // '' = anggaran keseluruhan (semua pengeluaran), selain itu = nama kategori.
            $table->string('category', 50)->default('')->after('year');
        });

        Schema::table('budgets', function (Blueprint $table) use ($driver) {
            $table->unique(['user_id', 'month', 'year', 'category']);
            if ($driver !== 'sqlite') {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('budgets', function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite') {
                $table->dropForeign(['user_id']);
            }
            $table->dropUnique(['user_id', 'month', 'year', 'category']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('budgets', function (Blueprint $table) use ($driver) {
            $table->unique(['user_id', 'month', 'year']);
            if ($driver !== 'sqlite') {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }
};
