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
        Schema::table('projects', function (Blueprint $table) {
            $table->text('revisi_reason')->nullable()->after('status');
            $table->timestamp('revisi_at')->nullable()->after('revisi_reason');
            $table->foreignId('revisi_by')->nullable()->constrained('users')->after('revisi_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['revisi_by']);
            $table->dropColumn(['revisi_reason', 'revisi_at', 'revisi_by']);
        });
    }
};
