<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi dana (rekening) + tautan Non-Project Expense pada catatan kas.
     */
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->foreignId('cash_account_id')->nullable()->after('sumber')->constrained('cash_accounts')->nullOnDelete();
            $table->foreignId('non_project_expense_id')->nullable()->after('payment_id')->constrained('non_project_expenses')->nullOnDelete();

            $table->index('cash_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_account_id');
            $table->dropConstrainedForeignId('non_project_expense_id');
        });
    }
};
