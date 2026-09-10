<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify cashflow/fund_transfer status by removing the approval workflow.
 *
 * Before : Draft → Waiting → Approved → Posted
 * After  : Draft → Posted  (approval step removed)
 *
 * Data migration: all 'waiting' and 'approved' entries → 'posted'.
 * Also drops the approval-specific audit columns that are no longer needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Promote waiting/approved cashflows to posted
        DB::table('cashflows')
            ->whereIn('status', ['waiting', 'approved'])
            ->update([
                'status'    => 'posted',
                'posted_by' => DB::raw('COALESCE(posted_by, approved_by, submitted_by, created_by)'),
                'posted_at' => DB::raw('COALESCE(posted_at, approved_at, updated_at)'),
            ]);

        // 2. Promote waiting/approved fund_transfers to posted
        DB::table('fund_transfers')
            ->whereIn('status', ['waiting', 'approved'])
            ->update([
                'status'    => 'posted',
                'posted_by' => DB::raw('COALESCE(posted_by, approved_by, submitted_by, created_by)'),
                'posted_at' => DB::raw('COALESCE(posted_at, approved_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        // No-op: cannot distinguish previously waiting/approved from posted
    }
};
