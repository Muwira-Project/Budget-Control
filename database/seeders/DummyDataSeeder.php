<?php

namespace Database\Seeders;

use App\Enums\CashflowSumber;
use App\Enums\ProjectJenis;
use App\Enums\ProjectStatus;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\Investor;
use App\Models\Kategori;
use App\Models\Mandor;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\MonitoringPeriod;
use App\Models\NumberSequence;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CashflowService;
use App\Services\PayableService;
use App\Services\PaymentService;
use App\Services\ReceivableService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Counter for dummy akun codes that are not part of the fixed COA.
     */
    protected int $akunCounter = 0;

    /**
     * Seed a small, focused demo dataset: 2 projects plus supporting master data.
     *
     * Run with: php artisan db:seed --class=DummyDataSeeder
     */
    public function run(): void
    {
        $this->seedKategoris();
        $this->seedMasterAkuns();
        $this->seedPartyMasterTypes();
        $this->seedDivisionMasterType();
        $this->seedVendors();
        $this->seedSuppliers();
        $this->seedMandors();
        $this->seedInvestors();
        $this->seedUsers();
        $this->seedProjects();
        $this->seedAllocationSamples();
        $this->seedBudgetPlans();
        $this->seedMonitoringPeriods();

        $this->syncSequenceFromMax('monitoring_period', (string) MonitoringPeriod::max('nomor'));
        $this->seedCashflows();
        $this->seedArAp();
        $this->seedNewFeatureSamples();
    }

    /**
     * Seed samples for new features:
     * - Cashflow manual entry with Project + Party tagging (auto-sync to Realisasi)
     * - Non-Project Allocation
     */
    protected function seedNewFeatureSamples(): void
    {
        $admin = User::where('email', 'admin@muwira.test')->first();
        $project1 = Project::where('kode', 'PRJ-2025-001')->first();
        $project2 = Project::where('kode', 'PRJ-2025-002')->first();
        $akunOperasional = Akun::where('kode_akun', '5-101')->first();
        $akunListrik = Akun::where('kode_akun', '5-102')->first();
        $akunATK = Akun::where('kode_akun', '5-103')->first();

        // Master Party items
        $vendorType = MasterType::where('kode', 'VENDOR')->first();
        $supplierType = MasterType::where('kode', 'SUPPLIER')->first();
        $investorType = MasterType::where('kode', 'INVESTOR')->first();
        $mandorType = MasterType::where('kode', 'MANDOR')->first();

        $vendor = MasterItem::where('master_type_id', $vendorType->id)->where('kode', 'VND-001')->first();
        $supplier = MasterItem::where('master_type_id', $supplierType->id)->where('kode', 'SPL-001')->first();
        $investor = MasterItem::where('master_type_id', $investorType->id)->where('kode', 'INV-001')->first();
        $mandor = MasterItem::where('master_type_id', $mandorType->id)->where('kode', 'MND-001')->first();

        // ============================================
        // 1. CASHIN MANUAL: Investor tag project + pihak
        // ============================================
        $akunPendapatan = Akun::where('kode_akun', '4-001')->first() ?? Akun::where('jenis_akun', 'pendapatan')->first();
        if ($project1 && $investor && $investorType && $akunPendapatan) {
            $cashIn = Cashflow::firstOrCreate(
                [
                    'tanggal' => '2026-07-01',
                    'jenis' => 'masuk',
                    'sumber' => CashflowSumber::Pendapatan->value,
                    'nominal' => 150000000,
                    'keterangan' => 'Investasi tambahan dari PT Mitra Investama untuk PRJ-2025-001',
                ],
                [
                    'cash_account_id' => CashAccount::defaultId(),
                    'akun_id' => $akunPendapatan->id,
                    'project_id' => $project1->id,
                    'pihak_type_id' => $investorType->id,
                    'pihak_item_id' => $investor->id,
                    'status' => 'approved', // create as approved, then post via service
                    'submitted_by' => $admin?->id,
                    'approved_by' => $admin?->id,
                    'approved_at' => now(),
                    'created_by' => $admin?->id,
                ]
            );

            // Post via service to trigger syncRealisasiFromCashflow
            if ($cashIn->wasRecentlyCreated || $cashIn->status->value === 'approved') {
                app(CashflowService::class)->post($cashIn);
            }
        }

        // ============================================
        // 2. CASHOUT MANUAL: Vendor tag project + pihak
        // ============================================
        if ($project2 && $vendor && $vendorType && $akunListrik) {
            $cashOut = Cashflow::firstOrCreate(
                [
                    'tanggal' => '2026-07-10',
                    'jenis' => 'keluar',
                    'sumber' => CashflowSumber::PengeluaranLain->value,
                    'nominal' => 25000000,
                    'keterangan' => 'Bayar tagihan listrik site PRJ-2025-002 ke PT Maju Jaya',
                ],
                [
                    'cash_account_id' => CashAccount::defaultId(),
                    'akun_id' => $akunListrik->id,
                    'project_id' => $project2->id,
                    'pihak_type_id' => $vendorType->id,
                    'pihak_item_id' => $vendor->id,
                    'status' => 'approved',
                    'submitted_by' => $admin?->id,
                    'approved_by' => $admin?->id,
                    'approved_at' => now(),
                    'created_by' => $admin?->id,
                ]
            );

            if ($cashOut->wasRecentlyCreated || $cashOut->status->value === 'approved') {
                app(CashflowService::class)->post($cashOut);
            }
        }

        // ============================================
        // 3. CASHOUT NON-PROJECT: Operational tanpa project, tapi tag pihak
        // ============================================
        if ($supplier && $supplierType && $akunATK) {
            $cashOutNonProj = Cashflow::firstOrCreate(
                [
                    'tanggal' => '2026-07-15',
                    'jenis' => 'keluar',
                    'sumber' => CashflowSumber::PengeluaranLain->value,
                    'nominal' => 5000000,
                    'keterangan' => 'Beli ATK kantor dari PT Sumber Material (non-project)',
                ],
                [
                    'cash_account_id' => CashAccount::defaultId(),
                    'akun_id' => $akunATK->id,
                    'project_id' => null,
                    'pihak_type_id' => $supplierType->id,
                    'pihak_item_id' => $supplier->id,
                    'status' => 'approved',
                    'submitted_by' => $admin?->id,
                    'approved_by' => $admin?->id,
                    'approved_at' => now(),
                    'created_by' => $admin?->id,
                ]
            );

            if ($cashOutNonProj->wasRecentlyCreated || $cashOutNonProj->status->value === 'approved') {
                app(CashflowService::class)->post($cashOutNonProj);
            }
        }

        // ============================================
        // 4. NON-PROJECT ALLOCATION (Budgeting page)
        // ============================================
        if ($akunOperasional) {
            ProjectAkun::updateOrCreate(
                [
                    'project_id' => null,
                    'akun_id' => $akunOperasional->id,
                ],
                [
                    'budget' => 100000000,
                    'allocation' => 100000000,
                    'status' => 'approved',
                    'approved_by' => $admin?->id,
                    'approved_at' => now(),
                ]
            );
        }

        // ============================================
        // 5. NON-PROJECT ALLOCATION DRAFT (staff can submit)
        // ============================================
        $staff = User::where('email', 'staff@muwira.test')->first();
        if ($staff && $akunListrik) {
            ProjectAkun::updateOrCreate(
                [
                    'project_id' => null,
                    'akun_id' => $akunListrik->id,
                ],
                [
                    'budget' => 50000000,
                    'allocation' => 50000000,
                    'status' => 'draft',
                    'created_by' => $staff->id,
                ]
            );
        }
    }

    /**
     * Sync the number sequence so the next generated number continues
     * after the highest number already present in the table.
     */
    protected function syncSequenceFromMax(string $type, string $maxNomor): void
    {
        if (! preg_match('/-(?<year>\d{4})-(?<number>\d+)$/', $maxNomor, $matches)) {
            return;
        }

        NumberSequence::updateOrCreate(
            ['type' => $type, 'year' => $matches['year']],
            ['last_number' => (int) $matches['number']],
        );
    }

    /**
     * Create the demo user accounts.
     */
    protected function seedUsers(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@muwira.test'],
            ['name' => 'Admin myfinance', 'password' => 'password', 'role' => 'admin'],
        );

        User::updateOrCreate(
            ['email' => 'staff@muwira.test'],
            ['name' => 'Staff myfinance', 'password' => 'password', 'role' => 'staff'],
        );
    }

    /**
     * Ensure the 4 party master types exist (flag AR + AP) so the party
     * dropdowns and filters keep working on a fresh (or migrated) DB.
     * The migration itself creates them; this is a safety net for repeated seeds.
     */
    protected function seedPartyMasterTypes(): void
    {
        $types = ['VENDOR' => 'Vendor', 'SUPPLIER' => 'Supplier', 'MANDOR' => 'Mandor', 'INVESTOR' => 'Investor'];

        foreach ($types as $kode => $nama) {
            MasterType::firstOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'deskripsi' => $nama.' - pihak transaksi',
                    'flag_ar' => true,
                    'flag_ap' => true,
                    'aktif' => true,
                    'is_system' => true,
                    'sort' => 10,
                ],
            );
        }
    }

    /**
     * Seed the Division master type and its items.
     * Flexible - admin can add/remove via Master -> Master Items.
     */
    protected function seedDivisionMasterType(): void
    {
        $divisionType = MasterType::firstOrCreate(
            ['kode' => 'DIVISION'],
            [
                'nama' => 'Division',
                'deskripsi' => 'Project division/department',
                'flag_project' => true,
                'flag_ar' => false,
                'flag_ap' => false,
                'aktif' => true,
                'is_system' => true,
                'sort' => 5,
            ],
        );

        $divisions = [
            ['kode' => 'CONSTRUCTION', 'nama' => 'Construction'],
            ['kode' => 'MEP', 'nama' => 'MEP'],
            ['kode' => 'CIVIL', 'nama' => 'Civil'],
            ['kode' => 'ARCHITECTURE', 'nama' => 'Architecture'],
            ['kode' => 'INTERIOR', 'nama' => 'Interior'],
            ['kode' => 'OTHERS', 'nama' => 'Others'],
        ];

        foreach ($divisions as $div) {
            MasterItem::firstOrCreate(
                ['master_type_id' => $divisionType->id, 'kode' => $div['kode']],
                ['nama' => $div['nama'], 'aktif' => true],
            );
        }
    }

    /**
     * Seed the vendor master data (jasa).
     */
    protected function seedVendors(): void
    {
        $vendors = [
            ['kode' => 'VND-001', 'nama' => 'PT Maju Jaya', 'telepon' => '021-5551001', 'alamat' => 'Jakarta Selatan'],
            ['kode' => 'VND-002', 'nama' => 'CV Karya Bangun', 'telepon' => '021-5551002', 'alamat' => 'Jakarta Pusat'],
            ['kode' => 'VND-003', 'nama' => 'PT Sinergi Sukses', 'telepon' => '022-5552001', 'alamat' => 'Bandung'],
            ['kode' => 'VND-004', 'nama' => 'PT Digital Kreasi', 'telepon' => '0274-5555001', 'alamat' => 'Yogyakarta'],
        ];

        foreach ($vendors as $vendorData) {
            $this->partyItem('VENDOR', $vendorData['kode'], $vendorData);
        }
    }

    /**
     * Seed the supplier master data (barang).
     */
    protected function seedSuppliers(): void
    {
        $suppliers = [
            ['kode' => 'SPL-001', 'nama' => 'PT Sumber Material', 'telepon' => '024-5554101', 'alamat' => 'Semarang'],
            ['kode' => 'SPL-002', 'nama' => 'CV Bahan Bangunan', 'telepon' => '021-5554102', 'alamat' => 'Jakarta Timur'],
            ['kode' => 'SPL-003', 'nama' => 'PT Nusantara Net', 'telepon' => '022-5554103', 'alamat' => 'Bandung'],
            ['kode' => 'SPL-004', 'nama' => 'PT Furniture Jaya', 'telepon' => '021-5554106', 'alamat' => 'Jakarta Barat'],
        ];

        foreach ($suppliers as $supplierData) {
            $this->partyItem('SUPPLIER', $supplierData['kode'], $supplierData);
        }
    }

    /**
     * Seed the mandor master data.
     */
    protected function seedMandors(): void
    {
        $mandors = [
            ['kode' => 'MND-001', 'nama' => 'Budi Santoso', 'telepon' => '0812-111-2001', 'alamat' => 'Jakarta Barat'],
            ['kode' => 'MND-002', 'nama' => 'Agus Salim', 'telepon' => '0813-111-2002', 'alamat' => 'Tangerang'],
        ];

        foreach ($mandors as $mandorData) {
            $this->partyItem('MANDOR', $mandorData['kode'], $mandorData);
        }
    }

    /**
     * Seed the investor master data.
     */
    protected function seedInvestors(): void
    {
        $investors = [
            ['kode' => 'INV-001', 'nama' => 'PT Mitra Investama', 'telepon' => '021-5553001', 'alamat' => 'Jakarta Selatan'],
            ['kode' => 'INV-002', 'nama' => 'Tuan Hartono', 'telepon' => '0815-111-3002', 'alamat' => 'Surabaya'],
        ];

        foreach ($investors as $investorData) {
            $this->partyItem('INVESTOR', $investorData['kode'], $investorData);
        }
    }

    /**
     * Create (or fetch) a party master item under the given type kode.
     *
     * @param  array<string, mixed>  $data
     */
    protected function partyItem(string $typeKode, string $kode, array $data): MasterItem
    {
        $type = MasterType::where('kode', $typeKode)->first();

        if ($type === null) {
            throw new \RuntimeException("Master type {$typeKode} is missing.");
        }

        return MasterItem::firstOrCreate(
            ['master_type_id' => $type->id, 'kode' => $kode],
            [
                'nama' => $data['nama'] ?? $kode,
                'aktif' => true,
                'data' => [
                    'telepon' => $data['telepon'] ?? null,
                    'alamat' => $data['alamat'] ?? null,
                ],
            ],
        );
    }

    /**
     * Seed the kategori master data.
     */
    protected function seedKategoris(): void
    {
        $kategoris = [
            ['kode' => 'KAT-001', 'nama' => 'Biaya Umum'],
            ['kode' => 'KAT-002', 'nama' => 'Vendor/Jasa'],
            ['kode' => 'KAT-003', 'nama' => 'Supplier'],
            ['kode' => 'KAT-004', 'nama' => 'Investor'],
            ['kode' => 'KAT-005', 'nama' => 'Material'],
            ['kode' => 'KAT-006', 'nama' => 'Pajak'],
        ];

        foreach ($kategoris as $kategoriData) {
            Kategori::firstOrCreate(
                ['kode' => $kategoriData['kode']],
                ['nama' => $kategoriData['nama']],
            );
        }
    }

    /**
     * Seed the master akun (COA).
     */
    protected function seedMasterAkuns(): void
    {
        $akuns = [
            ['kode_akun' => '1-001', 'nama_akun' => 'Kas Besar', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '4-001', 'nama_akun' => 'Pendapatan Jasa', 'jenis_akun' => 'pendapatan'],
            ['kode_akun' => '5-001', 'nama_akun' => 'Biaya Material', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '5-002', 'nama_akun' => 'Biaya Tenaga Kerja', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '5-003', 'nama_akun' => 'Biaya Peralatan', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '5-101', 'nama_akun' => 'Biaya Operasional', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '5-102', 'nama_akun' => 'Biaya Listrik', 'jenis_akun' => 'pengeluaran'],
            ['kode_akun' => '5-103', 'nama_akun' => 'Biaya ATK', 'jenis_akun' => 'pengeluaran'],
        ];

        foreach ($akuns as $akunData) {
            Akun::firstOrCreate(
                ['kode_akun' => $akunData['kode_akun']],
                [
                    'nama_akun' => $akunData['nama_akun'],
                    'jenis_akun' => $akunData['jenis_akun'],
                    'kategori_id' => Kategori::where('nama', 'Biaya Umum')->first()?->id,
                ],
            );
        }
    }

    /**
     * Seed the demo projects (2 active + progress) and their accounts.
     */
    protected function seedProjects(): void
    {
        $projects = [
            [
                'kode' => 'PRJ-2025-001',
                'po_number' => 'PO-2025-001',
                'nama' => 'Pembangunan Gedung Kantor',
                'lokasi' => 'Jakarta',
                'pic' => 'Arian',
                'division_id' => MasterItem::whereHas('masterType', fn($q) => $q->where('kode', 'DIVISION'))
                    ->where('kode', 'CONSTRUCTION')
                    ->first()?->id,
                'jenis' => ProjectJenis::Jasa->value,
                'qty' => 1,
                'satuan' => 'unit',
                'harga_satuan' => 1000000000,
                'pajak' => 11,
                'tanggal_mulai' => '2025-03-01',
                'target_selesai' => '2026-12-31',
                'status' => ProjectStatus::InProgress->value,
                'akun' => [
                    ['nama_akun' => 'Biaya Material', 'nominal' => 400000000, 'realisasi' => [
                        ['vendor' => 'PT Sumber Material', 'tanggal' => '2026-01-10', 'nominal' => 25000000, 'keterangan' => 'Beli material tahap 1'],
                        ['vendor' => 'PT Sumber Material', 'tanggal' => '2026-02-10', 'nominal' => 15000000, 'keterangan' => 'Beli material tahap 2'],
                    ]],
                    ['nama_akun' => 'Biaya Tenaga Kerja', 'nominal' => 300000000, 'realisasi' => [
                        ['vendor' => 'PT Maju Jaya', 'tanggal' => '2026-01-15', 'nominal' => 50000000, 'keterangan' => 'Upah tukang Januari'],
                        ['vendor' => 'PT Maju Jaya', 'tanggal' => '2026-02-15', 'nominal' => 50000000, 'keterangan' => 'Upah tukang Februari'],
                    ]],
                ],
            ],
            [
                'kode' => 'PRJ-2025-002',
                'po_number' => 'PO-2025-002',
                'nama' => 'Renovasi Ruang Rapat',
                'lokasi' => 'Jakarta',
                'pic' => 'Budi',
                'division_id' => MasterItem::whereHas('masterType', fn($q) => $q->where('kode', 'DIVISION'))
                    ->where('kode', 'INTERIOR')
                    ->first()?->id,
                'jenis' => ProjectJenis::Jasa->value,
                'qty' => 1,
                'satuan' => 'unit',
                'harga_satuan' => 200000000,
                'pajak' => 11,
                'tanggal_mulai' => '2025-09-01',
                'target_selesai' => '2026-06-30',
                'status' => ProjectStatus::Done->value,
                'akun' => [
                    ['nama_akun' => 'Biaya Material', 'nominal' => 100000000, 'realisasi' => [
                        ['vendor' => 'CV Bahan Bangunan', 'tanggal' => '2026-03-05', 'nominal' => 40000000, 'keterangan' => 'Beli cat & plafon'],
                    ]],
                    ['nama_akun' => 'Biaya Tenaga Kerja', 'nominal' => 60000000, 'realisasi' => [
                        ['vendor' => 'CV Karya Bangun', 'tanggal' => '2026-03-10', 'nominal' => 30000000, 'keterangan' => 'Upah tukang renovasi'],
                    ]],
                ],
            ],
        ];

        foreach ($projects as $projectData) {
            $akunList = $projectData['akun'];
            unset($projectData['akun']);

            $project = Project::firstOrCreate(
                ['kode' => $projectData['kode']],
                $projectData,
            );

            foreach ($akunList as $akunData) {
                $this->createAkun($project, $akunData);
            }
        }
    }

    /**
     * Create a budget (with its realisasi) for the given project.
     *
     * @param  array<string, mixed>  $akunData
     */
    protected function createAkun(Project $project, array $akunData): void
    {
        $akun = Akun::firstOrCreate(
            ['nama_akun' => $akunData['nama_akun']],
            [
                'kode_akun' => $this->nextAkunKode(),
                'jenis_akun' => 'pengeluaran',
                'kategori_id' => Kategori::where('nama', $this->kategoriFor($akunData['nama_akun']))->first()?->id,
            ],
        );

        ProjectAkun::firstOrCreate(
            ['project_id' => $project->id, 'akun_id' => $akun->id],
            ['budget' => $akunData['nominal'], 'allocation' => $akunData['nominal']],
        );

        foreach ($akunData['realisasi'] as $realisasiData) {
            // Data lama memakai vendor/supplier; sekarang pihak (party) adalah
            // master item dengan tipe VENDOR/SUPPLIER/MANDOR/INVESTOR.
            $party = $this->partyItem(
                $this->partyTypeKodeFor($realisasiData['vendor'] ?? null, $realisasiData['supplier'] ?? null),
                $this->partyKodeFor($realisasiData['vendor'] ?? null, $realisasiData['supplier'] ?? null),
                $realisasiData,
            );

            $kategori = Kategori::where('nama', $this->kategoriFor($akunData['nama_akun']))->first();

            Realisasi::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'akun_id' => $akun->id,
                    'pihak_type_id' => $party->master_type_id,
                    'pihak_item_id' => $party->id,
                    'kategori_id' => $kategori?->id,
                    'tanggal' => $realisasiData['tanggal'],
                    'nominal' => $realisasiData['nominal'],
                    'keterangan' => $realisasiData['keterangan'] ?? null,
                ],
            );
        }
    }

    /**
     * Resolve the party master type kode for a realisasi seed row.
     */
    protected function partyTypeKodeFor(?string $vendorNama, ?string $supplierNama): string
    {
        if ($supplierNama !== null) {
            return 'SUPPLIER';
        }

        return $vendorNama !== null ? 'VENDOR' : 'VENDOR';
    }

    /**
     * Resolve the party kode for a realisasi seed row (lookup by name).
     */
    protected function partyKodeFor(?string $vendorNama, ?string $supplierNama): string
    {
        if ($supplierNama !== null) {
            $item = MasterItem::whereHas('masterType', fn ($query) => $query->where('kode', 'SUPPLIER'))
                ->where('nama', $supplierNama)
                ->first();

            return $item?->kode ?? 'SPL-001';
        }

        $item = MasterItem::whereHas('masterType', fn ($query) => $query->where('kode', 'VENDOR'))
            ->where('nama', $vendorNama)
            ->first();

        return $item?->kode ?? 'VND-001';
    }

    /**
     * Seed one budget plan per demo project.
     */
    protected function seedBudgetPlans(): void
    {
        $projects = Project::orderBy('kode')->get();
        $periods = ['2026-08', '2026-09'];

        foreach ($projects as $index => $project) {
            $approved = $project->projectAkuns()->where('status', 'approved')->get();

            if ($approved->isEmpty()) {
                continue;
            }

            $plan = BudgetPlan::firstOrCreate(
                ['project_id' => $project->id, 'periode' => $periods[$index] ?? '2026-08'],
                [
                    'estimasi_pendapatan' => $project->nilai_total,
                    'estimasi_biaya' => 0,
                    'target_laba' => 0,
                ],
            );

            // Generate nomor for the budget plan
            if (empty($plan->nomor)) {
                $projectCode = $project->kode ?? 'NP';
                $periode = $periods[$index] ?? '2026-08';
                $lastPlan = BudgetPlan::where('project_id', $project->id)
                    ->where('periode', $periods[$index] ?? '2026-08')
                    ->latest('id')
                    ->first();
                $sequence = ($lastPlan?->id ?? 0) + 1;
                $plan->update(['nomor' => 'BP/'.$projectCode.'/'.$periode.'/'.$sequence]);
            }

            // Rebuild the plan items from the approved allocations and keep
            // estimasi_biaya & target_laba in sync with the item totals.
            $plan->items()->delete();

            foreach ($approved as $allocation) {
                BudgetPlanItem::create([
                    'budget_plan_id' => $plan->id,
                    'akun_id' => $allocation->akun_id,
                    'nominal' => $allocation->budget,
                ]);
            }

            $estimasiBiaya = $plan->items()->sum('nominal');

            $plan->update([
                'estimasi_biaya' => $estimasiBiaya,
                'target_laba' => (float) $plan->estimasi_pendapatan - (float) $estimasiBiaya,
            ]);
        }
    }

    /**
     * Seed allocation workflow samples (one draft, one waiting).
     */
    protected function seedAllocationSamples(): void
    {
        $draftProject = Project::where('kode', 'PRJ-2025-002')->first();
        $waitingProject = Project::where('kode', 'PRJ-2025-001')->first();
        $draftAkun = Akun::where('kode_akun', '5-103')->first();
        $waitingAkun = Akun::where('kode_akun', '5-102')->first();

        if ($draftProject && $draftAkun) {
            ProjectAkun::updateOrCreate(
                ['project_id' => $draftProject->id, 'akun_id' => $draftAkun->id],
                ['budget' => 10000000, 'allocation' => 10000000, 'status' => 'draft', 'approved_by' => null, 'approved_at' => null],
            );
        }

        if ($waitingProject && $waitingAkun) {
            ProjectAkun::updateOrCreate(
                ['project_id' => $waitingProject->id, 'akun_id' => $waitingAkun->id],
                ['budget' => 10000000, 'allocation' => 10000000, 'status' => 'waiting', 'approved_by' => null, 'approved_at' => null],
            );
        }
    }

    /**
     * Seed the monitoring periods demo data.
     */
    protected function seedMonitoringPeriods(): void
    {
        $project = Project::orderBy('id')->first();

        MonitoringPeriod::firstOrCreate(
            ['nomor' => 'MON-2026-001'],
            [
                'project_id' => $project?->id,
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->startOfMonth()->addDays(13)->toDateString(),
            ],
        );

        MonitoringPeriod::firstOrCreate(
            ['nomor' => 'MON-2026-002'],
            [
                'project_id' => null,
                'tanggal_mulai' => now()->startOfMonth()->addDays(14)->toDateString(),
                'tanggal_selesai' => now()->startOfMonth()->addDays(27)->toDateString(),
            ],
        );
    }

    /**
     * Seed cashflow entries (manual cash in and cash out).
     */
    protected function seedCashflows(): void
    {
        $akunOperasional = Akun::where('nama_akun', 'like', '%Operasional%')->orWhere('nama_akun', 'like', '%Umum%')->first()
            ?? Akun::where('jenis_akun', 'pengeluaran')->first();

        $keluar = [
            ['tanggal' => now()->startOfMonth()->toDateString(), 'nominal' => 3500000, 'akun_id' => $akunOperasional?->id, 'keterangan' => 'Listrik kantor bulan berjalan'],
            ['tanggal' => now()->startOfMonth()->subDays(20)->toDateString(), 'nominal' => 1500000, 'akun_id' => $akunOperasional?->id, 'keterangan' => 'ATK kantor'],
        ];

        foreach ($keluar as $entry) {
            if (Cashflow::where('sumber', CashflowSumber::PengeluaranLain->value)->where('nominal', $entry['nominal'])->where('keterangan', $entry['keterangan'])->doesntExist()) {
                Cashflow::create([
                    'tanggal' => $entry['tanggal'],
                    'jenis' => 'keluar',
                    'sumber' => CashflowSumber::PengeluaranLain->value,
                    'akun_id' => $entry['akun_id'],
                    'nominal' => $entry['nominal'],
                    'keterangan' => $entry['keterangan'],
                ]);
            }
        }

        $pendapatan = [
            ['tanggal' => '2026-07-05', 'nominal' => 250000000, 'keterangan' => 'Pendapatan termin 1 Pembangunan Gedung Kantor'],
            ['tanggal' => '2026-06-28', 'nominal' => 100000000, 'keterangan' => 'Pendapatan Renovasi Ruang Rapat'],
        ];

        foreach ($pendapatan as $entry) {
            if (Cashflow::where('sumber', CashflowSumber::Pendapatan->value)->where('nominal', $entry['nominal'])->where('keterangan', $entry['keterangan'])->doesntExist()) {
                Cashflow::create([
                    'tanggal' => $entry['tanggal'],
                    'jenis' => 'masuk',
                    'sumber' => CashflowSumber::Pendapatan->value,
                    'nominal' => $entry['nominal'],
                    'keterangan' => $entry['keterangan'],
                ]);
            }
        }
    }

    protected function seedArAp(): void
    {
        foreach (Project::where('status', 'done')->get() as $project) {
            app(ReceivableService::class)->createForProject($project);
        }

        foreach (Realisasi::whereNull('sumber')->get() as $realisasi) {
            app(PayableService::class)->syncFromRealisasi($realisasi);
        }

        $receivable = Receivable::whereHas('project', fn ($query) => $query->where('kode', 'PRJ-2025-002'))->first();

        if ($receivable && Payment::where('keterangan', 'Pelunasan sebagian piutang renovasi')->doesntExist()) {
            app(PaymentService::class)->createForReceivable($receivable, [
                'tanggal' => '2026-07-20',
                'nominal' => (float) $receivable->nominal * 0.5,
                'keterangan' => 'Pelunasan sebagian piutang renovasi',
            ]);
        }

        $payable = Payable::whereNotNull('supplier_id')->orderBy('id')->first()
            ?? Payable::whereHas('pihakItem.masterType', fn ($query) => $query->where('kode', 'SUPPLIER'))->orderBy('id')->first();

        if ($payable && Payment::where('keterangan', 'Pelunasan penuh material')->doesntExist()) {
            app(PaymentService::class)->createForPayable($payable, [
                'tanggal' => '2026-07-22',
                'nominal' => $payable->nominal,
                'keterangan' => 'Pelunasan penuh material',
            ]);
        }

        // Seed additional Receivables for AR import/export demo
        $this->seedSampleReceivables();
        $this->seedSamplePayables();
    }

    /**
     * Seed sample Receivables (AR) with different categories for demo.
     */
    protected function seedSampleReceivables(): void
    {
        $project1 = Project::where('kode', 'PRJ-2025-001')->first();
        $project2 = Project::where('kode', 'PRJ-2025-002')->first();

        // MasterItem for customer (Investor)
        $investorType = MasterType::where('kode', 'INVESTOR')->first();
        $customer = $investorType ? MasterItem::where('master_type_id', $investorType->id)->first() : null;

        $receivables = [
            // Billed (dibayar_lunas / posted)
            [
                'project_id' => $project1?->id,
                'pihak_type_id' => $investorType?->id,
                'pihak_item_id' => $customer?->id,
                'nomor_invoice' => 'INV-2026-001',
                'tanggal' => '2026-07-01',
                'jatuh_tempo' => '2026-07-31',
                'nominal' => 150000000,
                'nominal_dibayar' => 150000000,
                'keterangan' => 'Tagihan pembangunan gedung kantor Tahap 1',
            ],
            [
                'project_id' => $project2?->id,
                'pihak_type_id' => $investorType?->id,
                'pihak_item_id' => $customer?->id,
                'nomor_invoice' => 'INV-2026-002',
                'tanggal' => '2026-07-15',
                'jatuh_tempo' => '2026-08-15',
                'nominal' => 80000000,
                'nominal_dibayar' => 40000000,
                'keterangan' => 'Tagihan renovasi ruang rapat - 50%',
            ],
            // Unbilled (draft)
            [
                'project_id' => $project1?->id,
                'pihak_type_id' => $investorType?->id,
                'pihak_item_id' => $customer?->id,
                'nomor_invoice' => 'INV-2026-003',
                'tanggal' => '2026-08-01',
                'jatuh_tempo' => '2026-08-31',
                'nominal' => 200000000,
                'nominal_dibayar' => 0,
                'keterangan' => 'Tagihan tahap 2 - belum diterbitkan',
            ],
            // Inprogress (partially paid)
            [
                'project_id' => $project1?->id,
                'pihak_type_id' => $investorType?->id,
                'pihak_item_id' => $customer?->id,
                'nomor_invoice' => 'INV-2026-004',
                'tanggal' => '2026-06-15',
                'jatuh_tempo' => '2026-07-15',
                'nominal' => 100000000,
                'nominal_dibayar' => 30000000,
                'keterangan' => 'Tagihan material tambahan - on progress',
            ],
        ];

        foreach ($receivables as $data) {
            if ($data['project_id'] && $data['pihak_type_id'] && $data['pihak_item_id']) {
                Receivable::firstOrCreate(
                    ['nomor_invoice' => $data['nomor_invoice']],
                    $data
                );
            }
        }
    }

    /**
     * Seed sample Payables (AP) for demo.
     */
    protected function seedSamplePayables(): void
    {
        $project1 = Project::where('kode', 'PRJ-2025-001')->first();
        $project2 = Project::where('kode', 'PRJ-2025-002')->first();

        $vendorType = MasterType::where('kode', 'VENDOR')->first();
        $vendor = $vendorType ? MasterItem::where('master_type_id', $vendorType->id)->first() : null;

        $supplierType = MasterType::where('kode', 'SUPPLIER')->first();
        $supplier = $supplierType ? MasterItem::where('master_type_id', $supplierType->id)->first() : null;

        $payables = [
            [
                'project_id' => $project1?->id,
                'pihak_type_id' => $vendorType?->id,
                'pihak_item_id' => $vendor?->id,
                'akun_id' => Akun::where('kode_akun', '5-102')->first()?->id,
                'nomor_invoice' => 'BILL-2026-001',
                'tanggal' => '2026-07-01',
                'jatuh_tempo' => '2026-07-31',
                'nominal' => 50000000,
                'nominal_dibayar' => 50000000,
                'keterangan' => 'Tagihan upah tukang Januari',
            ],
            [
                'project_id' => $project2?->id,
                'pihak_type_id' => $supplierType?->id,
                'pihak_item_id' => $supplier?->id,
                'akun_id' => Akun::where('kode_akun', '5-001')->first()?->id,
                'nomor_invoice' => 'BILL-2026-002',
                'tanggal' => '2026-07-10',
                'jatuh_tempo' => '2026-08-10',
                'nominal' => 40000000,
                'nominal_dibayar' => 0,
                'keterangan' => 'Tagihan material cat & plafon',
            ],
            [
                'project_id' => $project1?->id,
                'pihak_type_id' => $vendorType?->id,
                'pihak_item_id' => $vendor?->id,
                'akun_id' => Akun::where('kode_akun', '5-102')->first()?->id,
                'nomor_invoice' => 'BILL-2026-003',
                'tanggal' => '2026-07-20',
                'jatuh_tempo' => '2026-08-20',
                'nominal' => 75000000,
                'nominal_dibayar' => 25000000,
                'keterangan' => 'Tagihan listrik site Juli',
            ],
        ];

        foreach ($payables as $data) {
            if ($data['project_id'] && $data['pihak_type_id'] && $data['pihak_item_id']) {
                Payable::firstOrCreate(
                    ['nomor_invoice' => $data['nomor_invoice']],
                    $data
                );
            }
        }
    }

    /**
     * Generate the next unique dummy akun code (outside the fixed COA).
     */
    protected function nextAkunKode(): string
    {
        return 'AKN-'.str_pad((string) ++$this->akunCounter, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Map a budget name to a realisasi category.
     */
    protected function kategoriFor(string $namaAkun): string
    {
        return match ($namaAkun) {
            'Biaya Material', 'Biaya Peralatan' => 'Material',
            'Biaya Tenaga Kerja' => 'Vendor/Jasa',
            default => 'Biaya Umum',
        };
    }
}
