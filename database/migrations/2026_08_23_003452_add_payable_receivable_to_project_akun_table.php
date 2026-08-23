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
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->foreignId('payable_id')->nullable()->after('outstanding_balance')->constrained()->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->after('payable_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receivable_id');
            $table->dropConstrainedForeignId('payable_id');
        });
    }
};
