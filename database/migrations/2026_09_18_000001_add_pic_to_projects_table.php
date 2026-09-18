<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah PIC foreign key ke projects table (referensi MasterItem type PIC).
     */
    public function up(): void
    {
        // Seed master type "PIC" untuk dropdown PIC project (via dynamic master).
        if (! DB::table('master_types')->where('kode', 'PIC')->exists()) {
            DB::table('master_types')->insert([
                'kode' => 'PIC',
                'nama' => 'PIC',
                'deskripsi' => 'Person In Charge project',
                'flag_ar' => false,
                'flag_ap' => false,
                'flag_project' => true,
                'aktif' => true,
                'is_system' => true,
                'sort' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('pic_id')->nullable()->after('pic')->constrained('master_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pic_id');
        });

        DB::table('master_items')->whereHas('masterType', fn ($q) => $q->where('kode', 'PIC'))->delete();
        DB::table('master_types')->where('kode', 'PIC')->delete();
    }
};