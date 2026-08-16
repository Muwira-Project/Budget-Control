<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic master (Opsi B): menu master bisa ditambah/edit/dihapus,
     * tiap master baru punya definisi kolom sendiri (master_fields),
     * dan nilai item disimpan generik pada kolom JSON (master_items.data).
     */
    public function up(): void
    {
        Schema::table('master_types', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('aktif');
            $table->unsignedInteger('sort')->default(0)->after('is_system');
        });

        Schema::create('master_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_type_id')->constrained('master_types')->cascadeOnDelete();
            $table->string('label');
            $table->string('tipe', 20)->default('text'); // text | textarea | number | date
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('master_type_id');
        });

        Schema::table('master_items', function (Blueprint $table) {
            $table->json('data')->nullable()->after('keterangan');
        });

        DB::table('master_types')
            ->where('kode', 'PROJECT_CATEGORY')
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->dropColumn('data');
        });

        Schema::dropIfExists('master_fields');

        Schema::table('master_types', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'sort']);
        });
    }
};
