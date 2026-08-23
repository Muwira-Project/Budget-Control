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
            $table->enum('type', ['ap', 'ar', 'other_income', 'other_outcome'])->default('other_outcome')->after('akun_id');
            $table->foreignId('pihak_type_id')->nullable()->after('type')->constrained('master_types')->nullOnDelete();
            $table->foreignId('pihak_item_id')->nullable()->after('pihak_type_id')->constrained('master_items')->nullOnDelete();
            $table->string('custom_name')->nullable()->after('pihak_item_id');
            $table->decimal('outstanding_balance', 15, 2)->nullable()->after('custom_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pihak_item_id');
            $table->dropConstrainedForeignId('pihak_type_id');
            $table->dropColumn(['type', 'custom_name', 'outstanding_balance']);
        });
    }
};
