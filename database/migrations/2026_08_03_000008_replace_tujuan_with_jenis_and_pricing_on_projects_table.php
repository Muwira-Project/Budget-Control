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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('tujuan');
            $table->string('jenis', 20)->default('barang')->after('lokasi');
            $table->decimal('qty', 15, 2)->nullable()->after('jenis');
            $table->string('satuan', 50)->nullable()->after('qty');
            $table->decimal('harga_satuan', 15, 2)->nullable()->after('satuan');
            $table->decimal('pajak', 5, 2)->default(0)->after('harga_satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'qty', 'satuan', 'harga_satuan', 'pajak']);
            $table->text('tujuan')->nullable()->after('lokasi');
        });
    }
};
