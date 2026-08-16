<?php

namespace Database\Seeders;

use App\Enums\ProjectJenis;
use App\Enums\ProjectStatus;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Cashflow;
use App\Models\Investor;
use App\Models\Kategori;
use App\Models\Mandor;
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
            Vendor::firstOrCreate(
                ['kode' => $vendorData['kode']],
                [
                    'nama' => $vendorData['nama'],
                    'telepon' => $vendorData['telepon'],
                    'alamat' => $vendorData['alamat'],
                ],
            );
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
            Supplier::firstOrCreate(
                ['kode' => $supplierData['kode']],
                [
                    'nama' => $supplierData['nama'],
                    'telepon' => $supplierData['telepon'],
                    'alamat' => $supplierData['alamat'],
                ],
            );
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
            Mandor::firstOrCreate(
                ['kode' => $mandorData['kode']],
                [
                    'nama' => $mandorData['nama'],
                    'telepon' => $mandorData['telepon'],
                    'alamat' => $mandorData['alamat'],
                ],
            );
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
            Investor::firstOrCreate(
                ['kode' => $investorData['kode']],
                [
                    'nama' => $investorData['nama'],
                    'telepon' => $investorData['telepon'],
                    'alamat' => $investorData['alamat'],
                ],
            );
        }
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
     * Seed the default chart of accounts (19 fixed akun).
     */
    protected function seedMasterAkuns(): void
    {
        $akuns = [
            ['kode_akun' => '4-100', 'nama_akun' => 'Maintenance', 'jenis_akun' => 'pendapatan', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '4-101', 'nama_akun' => 'Renovation', 'jenis_akun' => 'pendapatan', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '4-102', 'nama_akun' => 'Lainnya', 'jenis_akun' => 'pendapatan', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-100', 'nama_akun' => 'Bahan Baku dan Gudang', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Material'],
            ['kode_akun' => '5-101', 'nama_akun' => 'Upah', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Vendor/Jasa'],
            ['kode_akun' => '5-102', 'nama_akun' => 'Subkontraktor', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Vendor/Jasa'],
            ['kode_akun' => '5-103', 'nama_akun' => 'Proyek Lainnya', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-104', 'nama_akun' => 'Bonus dan Komisi', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-105', 'nama_akun' => 'Iklan dan Promosi', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-106', 'nama_akun' => 'Gaji dan Bonus', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-107', 'nama_akun' => 'BPJS Kesehatan dan Ketenagakerjaan', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-108', 'nama_akun' => 'Sewa', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-109', 'nama_akun' => 'Konsumsi Kantor dan ATK', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-110', 'nama_akun' => 'Penambahan/Pemeliharaan/Perawatan', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-111', 'nama_akun' => 'Telekomunikasi dan Perjalanan Dinas', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-112', 'nama_akun' => 'Listrik/Kebersihan/Keamanan', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-113', 'nama_akun' => 'Perbankan', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
            ['kode_akun' => '5-114', 'nama_akun' => 'Pajak', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Pajak'],
            ['kode_akun' => '5-115', 'nama_akun' => 'Lainnya', 'jenis_akun' => 'pengeluaran', 'kategori' => 'Biaya Umum'],
        ];

        foreach ($akuns as $akunData) {
            Akun::firstOrCreate(
                ['kode_akun' => $akunData['kode_akun']],
                [
                    'nama_akun' => $akunData['nama_akun'],
                    'jenis_akun' => $akunData['jenis_akun'],
                    'kategori_id' => Kategori::where('nama', $akunData['kategori'])->first()?->id,
                ],
            );
        }
    }

    /**
     * Seed the two demo projects with budgets and realisasi.
     */
    protected function seedProjects(): void
    {
        $projects = [
            [
                'kode' => 'PRJ-2025-001',
                'nama' => 'Pembangunan Gedung Kantor Muwira',
                'lokasi' => 'Jakarta Selatan',
                'status' => ProjectStatus::InProgress,
                'jenis' => ProjectJenis::Jasa,
                'qty' => 1,
                'satuan' => 'paket',
                'harga_satuan' => 525000000,
                'pajak' => 2,
                'akuns' => [
                    [
                        'kode_akun' => 'AKN-001', 'nama_akun' => 'Biaya Material', 'nominal' => 250000000,
                        'realisasi' => [
                            ['tanggal' => '2025-08-15', 'supplier' => 'PT Sumber Material', 'nominal' => 120000000, 'keterangan' => 'Pembelian besi dan semen tahap 1'],
                            ['tanggal' => '2025-11-20', 'supplier' => 'PT Sumber Material', 'nominal' => 85000000, 'keterangan' => 'Pembelian material tahap 2'],
                        ],
                    ],
                    [
                        'kode_akun' => 'AKN-002', 'nama_akun' => 'Biaya Tenaga Kerja', 'nominal' => 180000000,
                        'realisasi' => [
                            ['tanggal' => '2025-09-05', 'vendor' => 'CV Karya Bangun', 'nominal' => 60000000, 'keterangan' => 'Upah pekerja bulan September'],
                            ['tanggal' => '2025-10-05', 'vendor' => 'CV Karya Bangun', 'nominal' => 60000000, 'keterangan' => 'Upah pekerja bulan Oktober'],
                        ],
                    ],
                    [
                        'kode_akun' => 'AKN-003', 'nama_akun' => 'Biaya Peralatan', 'nominal' => 95000000,
                        'realisasi' => [
                            ['tanggal' => '2025-08-01', 'vendor' => 'PT Sinergi Sukses', 'nominal' => 45000000, 'keterangan' => 'Sewa crane 3 bulan'],
                        ],
                    ],
                ],
            ],
            [
                'kode' => 'PRJ-2025-002',
                'nama' => 'Renovasi Ruang Rapat Lantai 3',
                'lokasi' => 'Jakarta Pusat',
                'status' => ProjectStatus::Done,
                'jenis' => ProjectJenis::Jasa,
                'qty' => 1,
                'satuan' => 'paket',
                'harga_satuan' => 125000000,
                'pajak' => 2,
                'akuns' => [
                    [
                        'kode_akun' => 'AKN-001', 'nama_akun' => 'Biaya Material', 'nominal' => 60000000,
                        'realisasi' => [
                            ['tanggal' => '2025-04-10', 'supplier' => 'CV Bahan Bangunan', 'nominal' => 50000000, 'keterangan' => 'Material interior'],
                        ],
                    ],
                    [
                        'kode_akun' => 'AKN-002', 'nama_akun' => 'Biaya Tenaga Kerja', 'nominal' => 40000000,
                        'realisasi' => [
                            ['tanggal' => '2025-04-20', 'vendor' => 'CV Karya Bangun', 'nominal' => 40000000, 'keterangan' => 'Upah pekerja renovasi'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate(
                ['kode' => $projectData['kode']],
                [
                    'nama' => $projectData['nama'],
                    'lokasi' => $projectData['lokasi'],
                    'jenis' => $projectData['jenis'],
                    'qty' => $projectData['qty'],
                    'satuan' => $projectData['satuan'],
                    'harga_satuan' => $projectData['harga_satuan'],
                    'pajak' => $projectData['pajak'],
                    'status' => $projectData['status'],
                ],
            );

            if (! $project->wasRecentlyCreated) {
                continue;
            }

            foreach ($projectData['akuns'] as $akunData) {
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
            $vendor = isset($realisasiData['vendor'])
                ? Vendor::where('nama', $realisasiData['vendor'])->first()
                : null;

            $supplier = isset($realisasiData['supplier'])
                ? Supplier::where('nama', $realisasiData['supplier'])->first()
                : null;

            $kategori = Kategori::where('nama', $this->kategoriFor($akunData['nama_akun']))->first();

            Realisasi::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'akun_id' => $akun->id,
                    'vendor_id' => $vendor?->id,
                    'supplier_id' => $supplier?->id,
                    'kategori_id' => $kategori?->id,
                    'tanggal' => $realisasiData['tanggal'],
                    'nominal' => $realisasiData['nominal'],
                    'keterangan' => $realisasiData['keterangan'] ?? null,
                ],
            );
        }
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
     * Seed receivables for completed projects, payables from realisasi, and sample payments.
     */

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
            if (Cashflow::where('sumber', 'pengeluaran_lain')->where('nominal', $entry['nominal'])->where('keterangan', $entry['keterangan'])->doesntExist()) {
                Cashflow::create([
                    'tanggal' => $entry['tanggal'],
                    'jenis' => 'keluar',
                    'sumber' => 'pengeluaran_lain',
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
            if (Cashflow::where('sumber', 'pendapatan')->where('nominal', $entry['nominal'])->where('keterangan', $entry['keterangan'])->doesntExist()) {
                Cashflow::create([
                    'tanggal' => $entry['tanggal'],
                    'jenis' => 'masuk',
                    'sumber' => 'pendapatan',
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

        $payable = Payable::whereNotNull('supplier_id')->orderBy('id')->first();

        if ($payable && Payment::where('keterangan', 'Pelunasan penuh material')->doesntExist()) {
            app(PaymentService::class)->createForPayable($payable, [
                'tanggal' => '2026-07-22',
                'nominal' => $payable->nominal,
                'keterangan' => 'Pelunasan penuh material',
            ]);
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
