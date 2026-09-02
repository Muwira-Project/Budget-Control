<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow approval admin untuk batal/koreksi Settlement (Payment).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 15)->default('active')->after('jenis'); // active | pending_cancel | cancelled
            $table->text('void_reason')->nullable();
            $table->foreignId('void_requested_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('void_requested_at')->nullable();
            $table->text('void_review_note')->nullable();
            $table->foreignId('void_reviewed_by')->nullable()->after('void_review_note')->constrained('users')->nullOnDelete();
            $table->timestamp('void_reviewed_at')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('void_reviewed_by');
            $table->dropColumn(['status', 'void_reason', 'void_requested_by', 'void_requested_at', 'void_review_note', 'void_reviewed_at']);
        });
    }
};
