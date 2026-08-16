<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher untuk fund transfer (diakses via tombol aksi di Cash Activity).
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('fund_transfer_id')->nullable()->after('cashflow_id')->constrained('fund_transfers')->nullOnDelete();
            $table->index('fund_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_transfer_id');
        });
    }
};
