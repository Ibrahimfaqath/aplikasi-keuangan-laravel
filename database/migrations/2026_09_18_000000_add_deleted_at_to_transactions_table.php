<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Soft delete: baris tetap ada tapi disembunyikan dari query normal,
            // bisa dipulihkan dari halaman Sampah sebelum benar-benar dihapus.
            $table->softDeletes()->after('image');

            // Query Sampah selalu berawalan (user_id, deleted_at IS NOT NULL).
            $table->index(['user_id', 'deleted_at'], 'transactions_user_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_deleted_at_index');
            $table->dropSoftDeletes();
        });
    }
};
