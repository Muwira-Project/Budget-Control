<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('mandor_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            $table->foreignId('investor_id')->nullable()->after('mandor_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropForeign(['mandor_id']);
            $table->dropColumn(['investor_id', 'mandor_id']);
        });
    }
};
