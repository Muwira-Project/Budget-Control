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
        Schema::table('cashflows', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('akun_id')->constrained()->nullOnDelete();
            $table->foreignId('pihak_type_id')->nullable()->after('project_id')->constrained('master_types')->nullOnDelete();
            $table->foreignId('pihak_item_id')->nullable()->after('pihak_type_id')->constrained('master_items')->nullOnDelete();

            $table->index(['project_id', 'pihak_type_id', 'pihak_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'pihak_type_id', 'pihak_item_id']);
            $table->dropConstrainedForeignId('pihak_item_id');
            $table->dropConstrainedForeignId('pihak_type_id');
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
