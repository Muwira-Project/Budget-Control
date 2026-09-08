<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $vendorType = MasterType::where('kode', 'VENDOR')->first();
        $supplierType = MasterType::where('kode', 'SUPPLIER')->first();
        $mandorType = MasterType::where('kode', 'MANDOR')->first();
        $investorType = MasterType::where('kode', 'INVESTOR')->first();

        MasterItem::firstOrCreate(
            ['master_type_id' => $vendorType->id, 'nama' => 'PT Vendor Utama'],
            ['kode' => 'VENDOR-001', 'flag_ap' => true, 'aktif' => true]
        );
        MasterItem::firstOrCreate(
            ['master_type_id' => $vendorType->id, 'nama' => 'CV Vendor Kedua'],
            ['kode' => 'VENDOR-002', 'flag_ap' => true, 'aktif' => true]
        );

        MasterItem::firstOrCreate(
            ['master_type_id' => $supplierType->id, 'nama' => 'Supplier Baru'],
            ['kode' => 'SUPPLIER-001', 'flag_ap' => true, 'aktif' => true]
        );

        MasterItem::firstOrCreate(
            ['master_type_id' => $mandorType->id, 'nama' => 'Mandor Andi'],
            ['kode' => 'MANDOR-001', 'flag_ap' => true, 'aktif' => true]
        );

        MasterItem::firstOrCreate(
            ['master_type_id' => $investorType->id, 'nama' => 'Investor Budi'],
            ['kode' => 'INVESTOR-001', 'flag_ap' => true, 'aktif' => true]
        );

        $akun1 = Akun::firstOrCreate(['kode_akun' => 'AKUN-001'], ['nama_akun' => 'Biaya Operasional', 'jenis_akun' => 'pengeluaran']);
        $akun2 = Akun::firstOrCreate(['kode_akun' => 'AKUN-002'], ['nama_akun' => 'Biaya Material', 'jenis_akun' => 'pengeluaran']);
        $akun3 = Akun::firstOrCreate(['kode_akun' => 'AKUN-003'], ['nama_akun' => 'Biaya Tenaga Kerja', 'jenis_akun' => 'pengeluaran']);

        $project1 = Project::firstOrCreate(['kode' => 'PRJ-001'], ['nama' => 'Gedung A', 'lokasi' => 'Jakarta']);
        $project2 = Project::firstOrCreate(['kode' => 'PRJ-002'], ['nama' => 'Gedung B', 'lokasi' => 'Bandung']);

        foreach ([$akun1, $akun2, $akun3] as $akun) {
            foreach ([$project1, $project2] as $project) {
                ProjectAkun::firstOrCreate(
                    ['project_id' => $project->id, 'akun_id' => $akun->id],
                    ['budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']
                );
            }
        }

        $this->command->info('Test data created: Vendors, Supplier, Mandor, Investor, 3 Akuns, 2 Projects');
    }
}