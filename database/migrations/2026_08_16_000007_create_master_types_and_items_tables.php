<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data dinamis (K3): admin bisa membuat jenis master baru bebas + flag AR/AP.
     */
    public function up(): void
    {
        Schema::create('master_types', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('flag_ar')->default(false);
            $table->boolean('flag_ap')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_type_id')->constrained()->cascadeOnDelete();
            $table->string('kode', 50);
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['master_type_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_items');
        Schema::dropIfExists('master_types');
    }
};
