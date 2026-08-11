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
        Schema::table('realisasi', function (Blueprint $table) {
            $table->string('sumber', 30)->nullable()->after('keterangan');
            $table->unsignedBigInteger('sumber_id')->nullable()->after('sumber');
            $table->index(['sumber', 'sumber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropIndex(['sumber', 'sumber_id']);
            $table->dropColumn(['sumber_id', 'sumber']);
        });
    }
};
