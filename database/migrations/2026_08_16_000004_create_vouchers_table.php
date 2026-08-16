<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher: nomor seri otomatis + tanggal untuk tiap catatan kas (tanpa upload bukti).
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 50)->unique();
            $table->date('tanggal');
            $table->string('jenis', 10); // masuk | keluar
            $table->foreignId('cashflow_id')->nullable()->constrained('cashflows')->nullOnDelete();
            $table->string('keterangan', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('cashflow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
