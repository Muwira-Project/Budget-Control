<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_plans', function (Blueprint $table) {
            $table->string('nomor', 50)->unique()->nullable()->after('project_id');
        });

        // Generate nomor for existing budget plans
        \App\Models\BudgetPlan::with('project')->whereNull('nomor')->chunk(100, function ($plans) {
            foreach ($plans as $plan) {
                $projectCode = $plan->project?->kode ?? 'NP';
                $periode = $plan->periode ?? '0000-00';
                $plan->update([
                    'nomor' => 'BP/'.$projectCode.'/'.$periode.'/'.$plan->id,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_plans', function (Blueprint $table) {
            $table->dropColumn('nomor');
        });
    }
};