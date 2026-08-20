<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah amount dari bigInteger ke decimal(15,2) — pakai raw SQL agar tidak perlu doctrine/dbal di server
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN amount DECIMAL(15,2) NOT NULL DEFAULT 0');
        } elseif ($driver === 'sqlite') {
            // SQLite tidak support MODIFY COLUMN — buat tabel baru, copy, rename
            // (hanya untuk dev; di production pakai MySQL)
            DB::statement('CREATE TABLE transactions_new (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title VARCHAR(255), category VARCHAR(50), amount DECIMAL(15,2) NOT NULL DEFAULT 0, type VARCHAR(10), image VARCHAR(255), transaction_date DATE, created_at TIMESTAMP, updated_at TIMESTAMP)');
            DB::statement('INSERT INTO transactions_new (id, user_id, title, category, amount, type, image, transaction_date, created_at, updated_at) SELECT id, user_id, title, category, amount, type, image, transaction_date, created_at, updated_at FROM transactions');
            DB::statement('DROP TABLE transactions');
            DB::statement('ALTER TABLE transactions_new RENAME TO transactions');
        }

        // Index compound untuk query paling sering: user_id + transaction_date
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'transaction_date']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN amount BIGINT NOT NULL DEFAULT 0');
        }
    }
};
