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
        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 7);
            $table->decimal('estimasi_pendapatan', 15, 2)->default(0);
            $table->decimal('estimasi_biaya', 15, 2)->default(0);
            $table->decimal('target_laba', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('budget_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['budget_plan_id', 'akun_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_plan_items');
        Schema::dropIfExists('budget_plans');
    }
};
