<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buku besar: rekening fisik lokasi dana (Kas Kecil, Bank BCA, Bank Mandiri, dst).
     */
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->string('jenis', 10)->default('kas'); // kas | bank
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('status', 10)->default('active'); // active | inactive
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};