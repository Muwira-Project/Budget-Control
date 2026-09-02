<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional invoice number to payables and receivables.
     *
     * Uniqueness is enforced at the service layer (active rows only) because
     * SQLite cannot express a partial unique index that ignores soft-deleted
     * rows; the DB-level unique index would also block legitimate reuse of
     * an invoice number after a record is trashed.
     */
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->string('nomor_invoice', 100)->nullable()->after('tanggal');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->string('nomor_invoice', 100)->nullable()->after('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn('nomor_invoice');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn('nomor_invoice');
        });
    }
};
