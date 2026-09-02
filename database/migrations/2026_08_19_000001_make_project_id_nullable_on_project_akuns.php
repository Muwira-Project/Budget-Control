<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Non-project budget allocations (operational expenses).
     *
     * project_akuns.project_id becomes nullable so an allocation can belong
     * to the global (non-project) scope. Such allocations, once approved,
     * generate a cashflow (keluar, sumber=pengeluaran_lain) that follows the
     * regular cash approval flow (draft → waiting → approved → posted).
     */
    public function up(): void
    {
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restoring the NOT NULL constraint requires cleaning rows without a
        // project first; non-project allocations cannot be safely reverted.
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }
};
