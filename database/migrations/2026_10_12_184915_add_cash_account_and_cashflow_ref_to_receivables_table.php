<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add cash_account_id and cashflow_ref to receivables for import-linked cashflow tracking.
     */
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->foreignId('cash_account_id')
                ->nullable()
                ->after('akun_id')
                ->constrained('cash_accounts')
                ->nullOnDelete()
                ->comment('Cash account used when creating linked cashflow entry');

            $table->string('cashflow_ref', 100)
                ->nullable()
                ->after('cash_account_id')
                ->comment('Reference to external cashflow source (e.g., bank statement ref)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_account_id');
            $table->dropColumn('cashflow_ref');
        });
    }
};
