<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus kolom pic (string lama) setelah data termigrasi ke pic_id FK.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('pic');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('pic', 255)->nullable()->after('lokasi');
        });
    }
};