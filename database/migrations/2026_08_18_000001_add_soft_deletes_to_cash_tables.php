<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-delete support for cash-ledger entities.
     */
    public function up(): void
    {
        foreach ([
            'cash_accounts',
            'payments',
            'cashflows',
            'fund_transfers',
            'receivables',
            'payables',
        ] as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }

        // Unique kode per active account; allow re-using a kode after delete.
        Schema::table('cash_accounts', function (Blueprint $table) {
            if (Schema::hasIndex('cash_accounts', 'cash_accounts_kode_unique')) {
                $table->dropUnique('cash_accounts_kode_unique');
            }
        });

        // Remove uniqueness from receivables.project_id while preserving FK.
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['project_id']);

            if (Schema::hasIndex('receivables', 'receivables_project_id_unique')) {
                $table->dropUnique('receivables_project_id_unique');
            }

            $table->index('project_id');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            if (Schema::hasIndex('cash_accounts', 'cash_accounts_kode_unique')) {
                $table->dropUnique('cash_accounts_kode_unique');
            }

            $table->unique('kode', 'cash_accounts_kode_unique');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['project_id']);

            if (Schema::hasIndex('receivables', 'receivables_project_id_unique')) {
                $table->dropUnique('receivables_project_id_unique');
            }

            $table->unique('project_id', 'receivables_project_id_unique');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
        });

        foreach ([
            'cash_accounts',
            'payments',
            'cashflows',
            'fund_transfers',
            'receivables',
            'payables',
        ] as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
