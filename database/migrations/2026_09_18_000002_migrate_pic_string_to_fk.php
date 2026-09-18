<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi data pic (string) -> pic_id (FK ke master_items type PIC).
     */
    public function up(): void
    {
        // Pastikan MasterType PIC ada
        $picTypeId = DB::table('master_types')->where('kode', 'PIC')->value('id');

        if (! $picTypeId) {
            // Insert MasterType PIC jika belum ada (idempotent dengan migration sebelumnya)
            $picTypeId = DB::table('master_types')->insertGetId([
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

        // Ambil semua project yang punya pic string tapi belum punya pic_id
        $projects = DB::table('projects')
            ->whereNotNull('pic')
            ->where('pic', '!=', '')
            ->whereNull('pic_id')
            ->get(['id', 'pic']);

        foreach ($projects as $project) {
            $picName = trim($project->pic);

            // Cari MasterItem PIC yang cocok (by nama atau kode)
            $picItem = DB::table('master_items')
                ->where('master_type_id', $picTypeId)
                ->where('aktif', true)
                ->where(function ($q) use ($picName) {
                    $q->where('nama', $picName)
                      ->orWhere('kode', $picName);
                })
                ->first();

            if (! $picItem) {
                // Buat MasterItem PIC baru jika belum ada
                $picItemId = DB::table('master_items')->insertGetId([
                    'master_type_id' => $picTypeId,
                    'kode' => 'PIC-' . strtoupper(str_replace(' ', '', $picName)),
                    'nama' => $picName,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $picItemId = $picItem->id;
            }

            // Update project dengan pic_id
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['pic_id' => $picItemId]);
        }
    }

    public function down(): void
    {
        // Rollback: kosongkan pic_id, biarkan pic string seperti semula
        DB::table('projects')->whereNotNull('pic_id')->update(['pic_id' => null]);

        // Hapus MasterItem PIC yang dibuat otomatis (kode dimulai PIC- tapi bukan seed original)
        // Seed original pakai kode PIC-001, PIC-002, PIC-003
        DB::table('master_items')
            ->where('master_type_id', function ($q) {
                $q->select('id')->from('master_types')->where('kode', 'PIC');
            })
            ->where('kode', 'LIKE', 'PIC-%')
            ->whereNotIn('kode', ['PIC-001', 'PIC-002', 'PIC-003'])
            ->delete();
    }
};