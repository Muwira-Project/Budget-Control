<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-delete support for cash-ledger entities.
     *
     * - Adds deleted_at to cash_accounts, payments, cashflows, fund_transfers,
     *   receivables and payables so deleted records stay queryable/restorable.
     * - Rewrites the two unique indexes as partial unique indexes so a record
     *   can be re-created after a soft delete (SQLite: NULL deleted_at makes
     *   the index non-enforcing; MySQL uses the partial predicate).
     */
    public function up(): void
    {
        foreach (['cash_accounts', 'payments', 'cashflows', 'fund_transfers', 'receivables', 'payables'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes();
            });
        }

        // Unique kode per active account; allow re-using a kode after delete.
        // SQLite does not support partial unique indexes, so uniqueness is
        // enforced at the application layer (CashAccountService::create).
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropUnique('cash_accounts_kode_unique');
        });

        // One active receivable per project; allow a new receivable after delete.
        // Same rationale: enforced at the application layer.
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropUnique('receivables_project_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Idempotent: the unique indexes may or may not exist depending on
        // which migration version is being rolled back from.
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropUnique('cash_accounts_kode_unique');
        });
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->unique('kode', 'cash_accounts_kode_unique');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropUnique('receivables_project_id_unique');
        });
        Schema::table('receivables', function (Blueprint $table) {
            $table->unique('project_id', 'receivables_project_id_unique');
        });

        foreach (['cash_accounts', 'payments', 'cashflows', 'fund_transfers', 'receivables', 'payables'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
