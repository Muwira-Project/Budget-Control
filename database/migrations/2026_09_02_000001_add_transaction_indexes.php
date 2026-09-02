<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            if (!Schema::hasIndex('cashflows', 'idx_cashflows_status')) {
                $table->index('status', 'idx_cashflows_status');
            }

            if (!Schema::hasIndex('cashflows', 'idx_cashflows_tanggal')) {
                $table->index('tanggal', 'idx_cashflows_tanggal');
            }

            if (!Schema::hasIndex('cashflows', 'idx_cashflows_status_posted')) {
                $table->index(['status', 'posted_at'], 'idx_cashflows_status_posted');
            }
        });

        Schema::table('fund_transfers', function (Blueprint $table) {
            if (!Schema::hasIndex('fund_transfers', 'idx_fund_transfers_status')) {
                $table->index('status', 'idx_fund_transfers_status');
            }

            if (!Schema::hasIndex('fund_transfers', 'idx_fund_transfers_tanggal')) {
                $table->index('tanggal', 'idx_fund_transfers_tanggal');
            }

            if (!Schema::hasIndex('fund_transfers', 'idx_fund_transfers_status_posted')) {
                $table->index(['status', 'posted_at'], 'idx_fund_transfers_status_posted');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasIndex('payments', 'idx_payments_status')) {
                $table->index('status', 'idx_payments_status');
            }

            if (!Schema::hasIndex('payments', 'idx_payments_tanggal')) {
                $table->index('tanggal', 'idx_payments_tanggal');
            }

            if (!Schema::hasIndex('payments', 'idx_payments_receivable_status')) {
                $table->index(['receivable_id', 'status'], 'idx_payments_receivable_status');
            }

            if (!Schema::hasIndex('payments', 'idx_payments_payable_status')) {
                $table->index(['payable_id', 'status'], 'idx_payments_payable_status');
            }
        });

        Schema::table('receivables', function (Blueprint $table) {
            if (!Schema::hasIndex('receivables', 'idx_receivables_nominal_dibayar')) {
                $table->index('nominal_dibayar', 'idx_receivables_nominal_dibayar');
            }
        });

        Schema::table('payables', function (Blueprint $table) {
            if (!Schema::hasIndex('payables', 'idx_payables_nominal_dibayar')) {
                $table->index('nominal_dibayar', 'idx_payables_nominal_dibayar');
            }
        });

        Schema::table('realisasi', function (Blueprint $table) {
            if (!Schema::hasIndex('realisasi', 'idx_realisasi_project')) {
                $table->index('project_id', 'idx_realisasi_project');
            }

            if (!Schema::hasIndex('realisasi', 'idx_realisasi_tanggal')) {
                $table->index('tanggal', 'idx_realisasi_tanggal');
            }

            if (!Schema::hasIndex('realisasi', 'idx_realisasi_sumber')) {
                $table->index('sumber', 'idx_realisasi_sumber');
            }
        });

        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasIndex('vouchers', 'idx_vouchers_source')) {
                $table->index(['cashflow_id', 'fund_transfer_id'], 'idx_vouchers_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropIndex('idx_cashflows_status');
            $table->dropIndex('idx_cashflows_tanggal');
            $table->dropIndex('idx_cashflows_status_posted');
        });

        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->dropIndex('idx_fund_transfers_status');
            $table->dropIndex('idx_fund_transfers_tanggal');
            $table->dropIndex('idx_fund_transfers_status_posted');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_tanggal');
            $table->dropIndex('idx_payments_receivable_status');
            $table->dropIndex('idx_payments_payable_status');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropIndex('idx_receivables_nominal_dibayar');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex('idx_payables_nominal_dibayar');
        });

        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropIndex('idx_realisasi_project');
            $table->dropIndex('idx_realisasi_tanggal');
            $table->dropIndex('idx_realisasi_sumber');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_vouchers_source');
        });
    }
};
