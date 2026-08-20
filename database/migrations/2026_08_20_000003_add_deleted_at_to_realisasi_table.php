<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel realisasi memakai SoftDeletes di model, tapi migrasi asli
     * tidak pernah membuat kolom deleted_at. Tambahkan kolom tersebut
     * beserta kolom pihak terpadu (pihak_type_id / pihak_item_id) yang
     * dipakai setelah migrasi dynamic master.
     */
    public function up(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            if (! Schema::hasColumn('realisasi', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
