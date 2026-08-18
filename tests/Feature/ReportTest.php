<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\User;
use App\Services\CashflowService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_pages_are_admin_only(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach (['reports.profit-loss', 'reports.cash-flow', 'reports.aging'] as $route) {
            $this->actingAs($staff)->get(route($route))->assertForbidden();
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_profit_loss_calculates_revenue_cost_and_margin(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 1,
            'harga_satuan' => 1000000,
            'pajak' => 11,
            'tanggal_mulai' => '2026-08-01',
            'target_selesai' => '2026-08-31',
        ]);

        $akun = Akun::factory()->create();
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'tanggal' => '2026-08-01',
            'nominal' => 400000,
        ]);
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'tanggal' => '2026-08-02',
            'nominal' => 100000,
        ]);

        $report = app(ReportService::class)->profitLoss('2026-08-01', '2026-08-31');

        $this->assertEqualsWithDelta(1110000.0, $report['totals']['revenue'], 0.01);
        $this->assertEqualsWithDelta(500000.0, $report['totals']['cost'], 0.01);
        $this->assertEqualsWithDelta(610000.0, $report['totals']['profit'], 0.01);
        $this->assertEqualsWithDelta(55.0, $report['totals']['margin'], 0.01);
    }

    public function test_profit_loss_prorates_revenue_over_project_duration(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 1,
            'harga_satuan' => 1000000,
            'pajak' => 10, // nilai_total = 1.100.000
            'tanggal_mulai' => '2026-08-01',
            'target_selesai' => '2026-08-31', // 31 hari
        ]);

        // Laporan untuk minggu pertama (7 dari 31 hari) -> revenue prorata.
        $report = app(ReportService::class)->profitLoss('2026-08-01', '2026-08-07');

        $expectedRevenue = round(1100000 * (7 / 31), 2);
        $this->assertEqualsWithDelta($expectedRevenue, $report['totals']['revenue'], 0.01);
    }

    public function test_profit_loss_returns_zero_revenue_for_project_outside_period(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 1,
            'harga_satuan' => 1000000,
            'pajak' => 10,
            'tanggal_mulai' => '2026-09-01',
            'target_selesai' => '2026-09-30',
        ]);

        // Laporan Agustus -> proyek belum mulai, revenue 0.
        $report = app(ReportService::class)->profitLoss('2026-08-01', '2026-08-31');

        $this->assertSame(0.0, $report['totals']['revenue']);
        $this->assertSame(0.0, $report['totals']['profit']);
    }

    public function test_profit_loss_without_date_range_uses_full_contract_value(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 2,
            'harga_satuan' => 500000,
            'pajak' => 10, // nilai_total = 1.100.000
            'tanggal_mulai' => '2026-08-01',
            'target_selesai' => '2026-08-31',
        ]);

        $report = app(ReportService::class)->profitLoss();

        $this->assertEqualsWithDelta(1100000.0, $report['totals']['revenue'], 0.01);
    }

    public function test_profit_loss_revenue_full_when_period_covers_entire_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 1,
            'harga_satuan' => 1000000,
            'pajak' => 10,
            'tanggal_mulai' => '2026-08-01',
            'target_selesai' => '2026-08-31',
        ]);

        // Periode lebih lebar dari durasi proyek -> revenue penuh.
        $report = app(ReportService::class)->profitLoss('2026-07-01', '2026-09-30');

        $this->assertEqualsWithDelta(1100000.0, $report['totals']['revenue'], 0.01);
    }

    public function test_cash_flow_counts_only_posted_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $account = CashAccount::factory()->create(['saldo_awal' => 1000000]);

        app(CashflowService::class)->create([
            'tanggal' => '2026-08-01',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 500000,
            'cash_account_id' => $account->id,
        ]);
        app(CashflowService::class)->create([
            'tanggal' => '2026-08-02',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 200000,
            'cash_account_id' => $account->id,
            'status' => 'draft',
        ]);

        FundTransfer::create([
            'tanggal' => '2026-08-03',
            'dari_cash_account_id' => CashAccount::factory()->create()->id,
            'ke_cash_account_id' => $account->id,
            'nominal' => 300000,
            'status' => 'posted',
        ]);

        $report = app(ReportService::class)->cashFlow();

        $row = collect($report['rows'])->firstWhere('kode', $account->kode);

        $this->assertSame(1000000.0, (float) $row['saldo_awal']);
        $this->assertSame(500000.0, (float) $row['masuk']);
        $this->assertSame(300000.0, (float) $row['tr_in']);
        $this->assertSame(1800000.0, (float) $row['saldo_akhir']);
    }

    public function test_aging_buckets_sisa_only(): void
    {
        $admin = User::factory()->admin()->create();

        Receivable::factory()->create([
            'nominal' => 10000000,
            'nominal_dibayar' => 0,
            'jatuh_tempo' => '2026-01-01', // > 90 hari lewat
        ]);
        Receivable::factory()->create([
            'nominal' => 10000000,
            'nominal_dibayar' => 10000000, // lunas -> tidak masuk
            'jatuh_tempo' => '2026-01-01',
        ]);
        Receivable::factory()->create([
            'nominal' => 5000000,
            'nominal_dibayar' => 0,
            'jatuh_tempo' => '2026-08-30', // belum jatuh tempo -> current
        ]);

        $report = app(ReportService::class)->aging('2026-08-16');

        $this->assertSame(10000000.0, (float) $report['ar_totals']['over_90']);
        $this->assertSame(5000000.0, (float) $report['ar_totals']['current']);
        $this->assertSame(2, count($report['ar_rows']));
    }

    public function test_aging_ap_uses_party_and_sisa(): void
    {
        $admin = User::factory()->admin()->create();

        $payable = Payable::factory()->create([
            'nominal' => 7000000,
            'nominal_dibayar' => 0,
            'jatuh_tempo' => '2026-06-01', // 31-60 hari
        ]);

        $report = app(ReportService::class)->aging('2026-08-16');

        $this->assertSame(7000000.0, (float) $report['ap_totals']['61_90']);
        $this->assertSame($payable->vendor->nama, $report['ap_rows'][0]['label']);
    }
}
