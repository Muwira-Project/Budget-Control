<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan created_by pada cashflows (pemilik draft kegiatan kas).
     */
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('keterangan')->constrained('users')->nullOnDelete();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
