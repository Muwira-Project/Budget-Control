<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow approval kegiatan kas: draft -> waiting -> approved -> posted / rejected.
     */
    public function up(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->string('status', 20)->default('posted');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['status', 'approved_at', 'posted_at', 'rejected_at', 'rejection_reason']);
        });
    }
};
