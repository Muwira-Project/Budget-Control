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
        Schema::table('receivables', function (Blueprint $table) {
            $table->string('jenis_pajak')->nullable()->after('nominal_dibayar');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('jenis_pajak');
            $table->decimal('pajak_amount', 15, 2)->default(0)->after('discount_percent');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('pajak_include');
            $table->decimal('pajak_amount', 15, 2)->default(0)->after('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn(['jenis_pajak', 'discount_percent', 'pajak_amount']);
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'pajak_amount']);
        });
    }
};
