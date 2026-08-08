<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prevent duplicate ledger entries for one automated source transaction.
     */
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->unique('payment_request_id', 'cashflows_payment_request_unique');
            $table->unique('payment_id', 'cashflows_payment_unique');
        });
    }

    /**
     * Reverse the integrity indexes.
     */
    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropUnique('cashflows_payment_request_unique');
            $table->dropUnique('cashflows_payment_unique');
        });
    }
};
