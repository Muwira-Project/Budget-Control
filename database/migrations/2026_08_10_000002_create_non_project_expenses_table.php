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
        Schema::create('non_project_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('akun_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('mandor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('akun_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_project_expenses');
    }
};
