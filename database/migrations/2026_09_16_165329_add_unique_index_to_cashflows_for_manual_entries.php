<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->unique(
                ['tanggal', 'jenis', 'sumber', 'nominal', 'cash_account_id', 'payment_id'],
                'ux_cashflow_manual_unique'
            )->where('payment_id', null);
        });
    }

    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropUnique('ux_cashflow_manual_unique');
        });
    }
};