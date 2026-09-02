<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hold/Release dua sisi (K1): tahan pengeluaran (Payment Request) dan tahan penerimaan (Receivable).
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('keterangan');
            $table->foreignId('held_by')->nullable()->after('hold_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('held_at')->nullable()->after('held_by');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('keterangan');
            $table->foreignId('held_by')->nullable()->after('hold_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('held_at')->nullable()->after('held_by');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('held_by');
            $table->dropColumn(['hold_reason', 'held_at']);
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('held_by');
            $table->dropColumn(['hold_reason', 'held_at']);
        });
    }
};
