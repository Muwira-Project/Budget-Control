<?php

namespace Tests\Feature;

use App\Enums\PaymentJenis;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\MonitoringPeriod;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringVarianceDetailExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_variance_detail_export_downloads_for_admin(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Gedung Kantor']);
        $period = MonitoringPeriod::factory()->create([
            'project_id' => $project->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun' => '5-101',
            'nama_akun' => 'Biaya Material',
            'jenis_akun' => 'pengeluaran',
        ]);

        BudgetPlan::factory()->create(['project_id' => $project->id]);
        BudgetPlanItem::factory()->create([
            'budget_plan_id' => BudgetPlan::query()->first()->id,
            'akun_id' => $akun->id,
            'nominal' => 100000000,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'tanggal' => '2026-08-15',
            'nominal' => 30000000,
        ]);

        $response = $this->actingAs($user)->get(route('exports.monitoring-variance-detail', ['period' => $period, 'format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('Monitoring_Variance_Detail_' . $period->nomor . '_' . now()->format('Ymd') . '.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_variance_calculation_is_precise_and_not_double_counted(): void
    {
        // Budget 100jt, dua realisasi (20jt + 10jt = 30jt total)
        // Variance harus TEPAT 70jt — bukan 40jt (jika dihitung double) atau salah lainnya.
        $project = Project::factory()->create(['kode' => 'PRJ-CALC', 'nama' => 'Calc Test']);
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => $project->id,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun'  => '5-CALC',
            'nama_akun'  => 'Biaya Uji Hitung',
            'jenis_akun' => 'pengeluaran',
        ]);

        $budgetPlan = BudgetPlan::factory()->create(['project_id' => $project->id]);
        BudgetPlanItem::factory()->create([
            'budget_plan_id'  => $budgetPlan->id,
            'akun_id'         => $akun->id,
            'nominal'         => 100_000_000,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        // Dua transaksi realisasi terpisah
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akun->id,
            'tanggal'    => '2026-08-10',
            'nominal'    => 20_000_000,
        ]);
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akun->id,
            'tanggal'    => '2026-08-20',
            'nominal'    => 10_000_000,
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $budgetRows = collect($rows)->where('type', 'Budget');
        $actualRows = collect($rows)->where('type', 'Actual Out');

        $this->assertCount(1, $budgetRows, 'Harus ada tepat 1 budget row');
        $this->assertCount(2, $actualRows, 'Harus ada 2 baris Actual Out');

        $budgetRow = $budgetRows->first();

        // Variance TEPAT 70jt (100jt - 30jt) — bukan 40jt (double-count) atau 100jt (miss actual)
        $this->assertEquals(100_000_000.0, $budgetRow['budget'],   'Budget harus 100jt');
        $this->assertEquals(70_000_000.0,  $budgetRow['variance'], 'Variance harus tepat 70jt');

        // Total Actual Out rows harus 30jt
        $this->assertEquals(30_000_000.0, $actualRows->sum('actual_out'), 'Total Actual Out harus 30jt');

        // Kolom lain pada budget row harus 0 (tidak ada silang isi)
        $this->assertEquals(0.0, $budgetRow['actual_out']);
        $this->assertEquals(0.0, $budgetRow['cash_in']);
        $this->assertEquals(0.0, $budgetRow['ap_settlement']);
    }

    public function test_monitoring_variance_detail_rows_include_party_breakdown(): void
    {
        $project = Project::factory()->create(['kode' => 'PRJ-002', 'nama' => 'Rumah Sakit']);
        $period = MonitoringPeriod::factory()->create([
            'project_id' => $project->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun' => '6-301',
            'nama_akun' => 'Material Bangunan',
            'jenis_akun' => 'pengeluaran',
        ]);

        $vendorType = MasterType::firstOrCreate(['kode' => 'VENDOR', 'nama' => 'Vendor'], ['aktif' => true]);
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id, 'nama' => 'PT Maju Jaya']);

        $investorType = MasterType::firstOrCreate(['kode' => 'INVESTOR', 'nama' => 'Investor'], ['aktif' => true]);
        $investor = MasterItem::factory()->create(['master_type_id' => $investorType->id, 'nama' => 'PT Modal Nusantara']);

        $budgetPlan = BudgetPlan::factory()->create(['project_id' => $project->id]);
        BudgetPlanItem::factory()->create([
            'budget_plan_id' => $budgetPlan->id,
            'akun_id' => $akun->id,
            'nominal' => 100000000,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $vendor->id,
            'tanggal' => '2026-08-15',
            'nominal' => 30000000,
        ]);

        $receivable = Receivable::factory()->create([
            'project_id' => $project->id,
            'pihak_type_id' => $investorType->id,
            'pihak_item_id' => $investor->id,
            'tanggal' => '2026-08-05',
            'nominal' => 50000000,
        ]);

        Payment::factory()->create([
            'receivable_id' => $receivable->id,
            'tanggal' => '2026-08-12',
            'nominal' => 50000000,
            'jenis' => PaymentJenis::Masuk,
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $this->assertNotEmpty($rows);
        $pihakList = implode(' ', array_column($rows, 'pihak'));
        $this->assertStringContainsString('PT Maju Jaya', $pihakList);
        $this->assertStringContainsString('PT Modal Nusantara', $pihakList);
    }

    public function test_non_project_realisasi_included_in_global_period_export(): void
    {
        // Global period (no project_id) — semua realisasi dalam rentang masuk,
        // termasuk realisasi dari project manapun (periode global tidak mem-filter per project)
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => null,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun'  => '6-999',
            'nama_akun'  => 'Biaya Listrik',
            'jenis_akun' => 'pengeluaran',
        ]);

        $project = Project::factory()->create(['kode' => 'PRJ-GLOBAL', 'nama' => 'Biaya Umum']);

        // Realisasi biaya listrik di project umum — global period harus menangkap ini
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akun->id,
            'tanggal'    => '2026-08-20',
            'nominal'    => 5000000,
            'keterangan' => 'Bayar listrik kantor',
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $this->assertNotEmpty($rows);
        $types = array_column($rows, 'type');
        $this->assertContains('Actual Out', $types);

        $actualRow = collect($rows)->firstWhere('type', 'Actual Out');
        $this->assertEquals(5000000.0, $actualRow['actual_out']);
        $this->assertStringContainsString('Biaya Listrik', $actualRow['account']);
    }

    public function test_non_project_investor_cash_in_included_in_global_period_export(): void
    {
        // Global period — Cash In investor dari SEMUA project harus masuk.
        // Receivables selalu terikat ke project (DB NOT NULL constraint),
        // tapi period global tidak mem-filter project sehingga semua AR masuk.
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => null,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $project = Project::factory()->create(['kode' => 'PRJ-INV', 'nama' => 'Project Investor']);

        $investorType = MasterType::firstOrCreate(['kode' => 'INVESTOR', 'nama' => 'Investor'], ['aktif' => true]);
        $investor = MasterItem::factory()->create(['master_type_id' => $investorType->id, 'nama' => 'PT Investor Global']);

        // Receivable tanpa project (investor setoran modal global)
        $receivable = Receivable::factory()->create([
            'project_id'    => $project->id,
            'pihak_type_id' => $investorType->id,
            'pihak_item_id' => $investor->id,
            'tanggal'       => '2026-08-03',
            'nominal'       => 200000000,
        ]);

        Payment::factory()->create([
            'receivable_id' => $receivable->id,
            'tanggal'       => '2026-08-10',
            'nominal'       => 200000000,
            'jenis'         => PaymentJenis::Masuk,
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $this->assertNotEmpty($rows);
        $types = array_column($rows, 'type');
        $this->assertContains('Cash In (AR)', $types);

        $cashInRow = collect($rows)->firstWhere('type', 'Cash In (AR)');
        $this->assertEquals(200000000.0, $cashInRow['cash_in']);
        $this->assertStringContainsString('PT Investor Global', $cashInRow['pihak']);
    }

    public function test_realisasi_with_unbudgeted_akun_still_appears_in_export(): void
    {
        // Period dengan budget plan, tapi ada realisasi di akun LAIN (non-budgeted) — misal bayar listrik
        $project = Project::factory()->create(['kode' => 'PRJ-003', 'nama' => 'Office Project']);
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => $project->id,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akunBudget = Akun::factory()->create([
            'kode_akun'  => '5-001',
            'nama_akun'  => 'Biaya Material',
            'jenis_akun' => 'pengeluaran',
        ]);

        $akunListrik = Akun::factory()->create([
            'kode_akun'  => '5-999',
            'nama_akun'  => 'Biaya Listrik',
            'jenis_akun' => 'pengeluaran',
        ]);

        $budgetPlan = BudgetPlan::factory()->create(['project_id' => $project->id]);
        BudgetPlanItem::factory()->create([
            'budget_plan_id'  => $budgetPlan->id,
            'akun_id'         => $akunBudget->id,
            'nominal'         => 50000000,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        // Realisasi akun yang ADA di budget
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akunBudget->id,
            'tanggal'    => '2026-08-10',
            'nominal'    => 20000000,
        ]);

        // Realisasi akun yang TIDAK ADA di budget (biaya listrik non-budgeted)
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akunListrik->id,
            'tanggal'    => '2026-08-15',
            'nominal'    => 3000000,
            'keterangan' => 'Bayar listrik bulan Agustus',
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $actualRows = collect($rows)->where('type', 'Actual Out')->values();
        $this->assertCount(2, $actualRows, 'Both budgeted and non-budgeted realisasi must appear');

        $accounts = $actualRows->pluck('account')->all();
        $this->assertTrue(
            collect($accounts)->contains(fn ($a) => str_contains($a, 'Biaya Listrik')),
            'Non-budgeted electricity expense must appear in export'
        );
    }

    public function test_manual_cashflow_without_project_tag_appears_as_cash_activity(): void
    {
        // Cashflow manual Posted tanpa project/pihak tag (misal bayar listrik kantor langsung)
        // HARUS muncul di export sebagai 'Cash Activity (Out)' dan tidak double-count
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => null,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun'  => '5-CFT',
            'nama_akun'  => 'Biaya Operasional Kantor',
            'jenis_akun' => 'pengeluaran',
        ]);

        // Cashflow manual TANPA project_id dan pihak — tidak ter-sync ke Realisasi
        \App\Models\Cashflow::factory()->create([
            'tanggal'    => '2026-08-18',
            'jenis'      => 'keluar',
            'sumber'     => 'pengeluaran_lain',
            'akun_id'    => $akun->id,
            'project_id' => null,
            'pihak_item_id' => null,
            'nominal'    => 8_000_000,
            'status'     => \App\Enums\KasStatus::Posted,
            'payment_id' => null,
            'keterangan' => 'Bayar listrik & air kantor Agustus',
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        $this->assertNotEmpty($rows);
        $types = array_column($rows, 'type');
        $this->assertContains('Cash Activity (Out)', $types, 'Manual cashflow harus muncul sebagai Cash Activity (Out)');

        $caRow = collect($rows)->firstWhere('type', 'Cash Activity (Out)');
        $this->assertEquals(8_000_000.0, $caRow['actual_out']);
        $this->assertEquals(0.0, $caRow['cash_in']);
        $this->assertStringContainsString('Biaya Operasional Kantor', $caRow['account']);
    }

    public function test_manual_cashflow_synced_to_realisasi_is_not_double_counted(): void
    {
        // Cashflow manual dengan project+pihak tag → di-sync ke Realisasi oleh CashflowService
        // Export harus menampilkan SEKALI saja (via Realisasi), bukan dua kali
        $project = Project::factory()->create(['kode' => 'PRJ-DBLCK', 'nama' => 'Double Check']);
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => $project->id,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create([
            'kode_akun'  => '5-SYNC',
            'nama_akun'  => 'Biaya Vendor Sync',
            'jenis_akun' => 'pengeluaran',
        ]);

        $kategori = \App\Models\Kategori::factory()->create(['nama' => 'Pengeluaran']);

        // Cashflow sudah punya Realisasi (sumber=manual, sumber_id=cf_id) → sudah ter-sync
        $cf = \App\Models\Cashflow::factory()->create([
            'tanggal'    => '2026-08-12',
            'jenis'      => 'keluar',
            'sumber'     => 'pengeluaran_lain',
            'akun_id'    => $akun->id,
            'project_id' => $project->id,
            'nominal'    => 15_000_000,
            'status'     => \App\Enums\KasStatus::Posted,
            'payment_id' => null,
            'keterangan' => 'Bayar vendor sync',
        ]);

        // Buat Realisasi yang sudah di-sync dari cashflow ini (sumber=manual, sumber_id=cf->id)
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akun->id,
            'tanggal'    => '2026-08-12',
            'nominal'    => 15_000_000,
            'sumber'     => Realisasi::SUMBER_MANUAL,
            'sumber_id'  => $cf->id,
            'keterangan' => 'Dari cashflow #' . $cf->id,
            'kategori_id' => $kategori->id,
        ]);

        $rows = app(\App\Services\MonitoringPeriodService::class)->varianceDetailRows($period);

        // Hanya ada 1 row Actual Out (dari Realisasi), bukan 2
        $actualRows = collect($rows)->where('type', 'Actual Out');
        $caRows     = collect($rows)->where('type', 'Cash Activity (Out)');

        $this->assertCount(1, $actualRows, 'Hanya 1 baris Actual Out dari Realisasi');
        $this->assertCount(0, $caRows, 'Tidak boleh ada baris Cash Activity (sudah ter-sync ke Realisasi)');
        $this->assertEquals(15_000_000.0, $actualRows->sum('actual_out'), 'Total Actual Out harus 15jt, tidak double');
    }

    public function test_export_includes_total_konsolidasi_and_matches_ui_totals(): void
    {
        $project = Project::factory()->create(['kode' => 'PRJ-TOTAL', 'nama' => 'Consolidation Test']);
        $period = MonitoringPeriod::factory()->create([
            'project_id'      => $project->id,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        $akunBudget = Akun::factory()->create([
            'kode_akun'  => '5-BG',
            'nama_akun'  => 'Biaya Material Proyek',
            'jenis_akun' => 'pengeluaran',
        ]);

        $akunNonProject = Akun::factory()->create([
            'kode_akun'  => '5-OP',
            'nama_akun'  => 'Biaya Operasional Umum',
            'jenis_akun' => 'pengeluaran',
        ]);

        // 1. Budget Plan: 100jt
        $budgetPlan = BudgetPlan::factory()->create(['project_id' => $project->id]);
        BudgetPlanItem::factory()->create([
            'budget_plan_id'  => $budgetPlan->id,
            'akun_id'         => $akunBudget->id,
            'nominal'         => 100_000_000,
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        // 2. Realisasi Proyek: 40jt
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id'    => $akunBudget->id,
            'tanggal'    => '2026-08-10',
            'nominal'    => 40_000_000,
        ]);

        // 3. Cashflow Keluar Non-Project (Listrik): 5jt
        \App\Models\Cashflow::factory()->create([
            'tanggal'    => '2026-08-15',
            'jenis'      => 'keluar',
            'sumber'     => 'pengeluaran_lain',
            'akun_id'    => $akunNonProject->id,
            'project_id' => null,
            'nominal'    => 5_000_000,
            'status'     => \App\Enums\KasStatus::Posted,
            'payment_id' => null,
            'keterangan' => 'Listrik kantor Agustus',
        ]);

        // 4. Cashflow Masuk (Investor): 50jt
        \App\Models\Cashflow::factory()->create([
            'tanggal'    => '2026-08-05',
            'jenis'      => 'masuk',
            'sumber'     => 'pemasukan_manual',
            'project_id' => null,
            'nominal'    => 50_000_000,
            'status'     => \App\Enums\KasStatus::Posted,
            'payment_id' => null,
            'keterangan' => 'Setoran modal investor',
        ]);

        $service = app(\App\Services\MonitoringPeriodService::class);
        $export = new \App\Exports\MonitoringVarianceDetailExport($period);
        $collection = $export->collection();

        // Verifikasi Total UI
        $uiBudget = $service->budgetTotal($period);
        $uiActualOut = $service->actualTotal($period);
        $uiActualIn = $service->actualInTotal($period);
        $uiVariance = $uiBudget - $uiActualOut;

        $this->assertEquals(100_000_000.0, $uiBudget);
        $this->assertEquals(45_000_000.0, $uiActualOut); // 40jt realisasi + 5jt listrik
        $this->assertEquals(50_000_000.0, $uiActualIn);  // 50jt investor
        $this->assertEquals(55_000_000.0, $uiVariance);  // 100jt - 45jt

        // Verifikasi Baris Total Konsolidasi di Excel
        $totalRow = $collection->last();
        $this->assertEquals('TOTAL KONSOLIDASI', $totalRow['po_number']);
        $this->assertEquals(100_000_000.0, $totalRow['budget']);
        $this->assertEquals(45_000_000.0, $totalRow['actual_out']);
        $this->assertEquals(50_000_000.0, $totalRow['cash_in']);
        $this->assertEquals(55_000_000.0, $totalRow['variance']);
    }
}
