<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('departemen')->nullable()->after('name');
            $table->date('tanggal_masuk')->nullable()->after('departemen');
            $table->string('lokasi')->nullable()->after('tanggal_masuk');
            $table->string('foto')->nullable()->after('lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto', 'lokasi', 'tanggal_masuk', 'departemen']);
        });
    }
};
