<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemindahan dana antar rekening (bukan pendapatan/beban).
     */
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('dari_cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->foreignId('ke_cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};