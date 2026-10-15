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
        Schema::table('receivables', function (Blueprint $table) {
            $table->foreignId('akun_id')
                ->nullable()
                ->after('pihak_item_id')
                ->constrained('akuns')
                ->nullOnDelete()
                ->comment('COA account for AR revenue recognition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['akun_id']);
            $table->dropColumn('akun_id');
        });
    }
};
