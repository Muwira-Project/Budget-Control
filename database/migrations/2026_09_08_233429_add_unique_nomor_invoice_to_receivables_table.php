<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make project_id nullable and add unique constraint on nomor_invoice
     * to match payables behavior for optional project code import.
     */
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
            $table->unique('nomor_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropUnique('receivables_nomor_invoice_unique');
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};