<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus total modul Payment Request & Non-Project Expense (menu + data),
     * tambahkan akun COA pada cashflow untuk pengeluaran lain (biaya sewa, utility, dst).
     */
    public function up(): void
    {
        // Index payables berikut mereferensikan kolom 'status' yang tidak pernah ada di payables.
        // SQLite memvalidasi index saat DDL berikutnya, jadi drop lebih dulu agar drop column aman.

        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropUnique('cashflows_payment_request_unique');
            $table->dropConstrainedForeignId('payment_request_id');
            $table->dropConstrainedForeignId('non_project_expense_id');
            $table->foreignId('akun_id')->nullable()->after('cash_account_id')->constrained('akuns')->nullOnDelete();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_request_id');
        });

        Schema::dropIfExists('non_project_expenses');
        Schema::dropIfExists('payment_requests');
    }

    public function down(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 50)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('mandor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal');
            $table->date('jatuh_tempo')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->string('prioritas', 10)->default('medium');
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('status');
        });

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

        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('akun_id');
            $table->foreignId('payment_request_id')->nullable()->after('sumber')->constrained('payment_requests')->nullOnDelete();
            $table->foreignId('non_project_expense_id')->nullable()->after('payment_request_id')->constrained('non_project_expenses')->nullOnDelete();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('payment_request_id')->nullable()->after('realisasi_id')->constrained('payment_requests')->nullOnDelete();
        });
    }
};
