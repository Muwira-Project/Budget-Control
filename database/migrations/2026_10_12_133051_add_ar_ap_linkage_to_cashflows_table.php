<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add receivable_id and payable_id FK to cashflows for direct AR/AP linkage.
     * Allows import to link cash entries directly to AR/AP records without going through Payment.
     * Nullable: payment_id takes precedence for settlement-based entries.
     */
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->foreignId('receivable_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('receivables')
                ->nullOnDelete();

            $table->foreignId('payable_id')
                ->nullable()
                ->after('receivable_id')
                ->constrained('payables')
                ->nullOnDelete();

            // Add index for common queries: "find all cashflows for this receivable/payable"
            $table->index('receivable_id');
            $table->index('payable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receivable_id');
            $table->dropConstrainedForeignId('payable_id');
        });
    }
};
