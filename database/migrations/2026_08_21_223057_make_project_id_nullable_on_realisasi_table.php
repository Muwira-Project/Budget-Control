<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make project_id nullable on realisasi table for non-project cashflow manual entries.
     */
    public function up(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     * Restoring NOT NULL requires cleaning non-project realisasi rows first.
     */
    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }
};
