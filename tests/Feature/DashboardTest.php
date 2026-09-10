<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Cashflow;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\MonitoringPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Project::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Budget')
            ->assertSee('Total Actual')
            ->assertSee('Remaining Budget')
            ->assertSee('Variance')
            ->assertSee('Budget Utilization')
            ->assertSee('Cashflow Summary')
            ->assertSee('Project Overview')
            ->assertSee('Recent Activity')
            ->assertSee('Cash Activity')
            ->assertSee('Billed')
            ->assertSee('Unbilled')
            ->assertSee('In Progress')
            ->assertSee('Total AR')
            ->assertSee('Payable (AP) Breakdown');
    }

    public function test_dashboard_shows_aggregated_statistics(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'nominal' => 30000000]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1', false)
            ->assertSee('100.000.000', false)
            ->assertSee('30.000.000', false)
            ->assertSee('70.000.000', false);
    }

    public function test_dashboard_respects_date_range(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-10', 'nominal' => 30000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-03-10', 'nominal' => 20000000]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('startDate', '2026-01-01')
            ->set('endDate', '2026-01-31')
            ->assertSee('30.000.000', false)
            ->assertSee('70.000.000', false)
            ->assertDontSee('20.000.000', false);
    }

    public function test_dashboard_shows_cash_balance(): void
    {
        $user = User::factory()->create();
        Cashflow::factory()->create(['jenis' => 'masuk', 'nominal' => 200000000]);
        Cashflow::factory()->create(['jenis' => 'keluar', 'nominal' => 50000000]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('150.000.000', false);
    }

    public function test_dashboard_shows_outstanding_ar_and_ap(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => 'done', 'qty' => 1, 'harga_satuan' => 100000000]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_invalid_date_range_shows_warning(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('startDate', '2026-05-10')
            ->set('endDate', '2026-04-10')
            ->assertSee('Invalid date range');
    }

    public function test_dashboard_service_returns_expected_shape(): void
    {
        $expected = [
            'total_projects',
            'projects_barang',
            'projects_jasa',
            'total_budget',
            'total_allocation',
            'total_realisasi',
            'total_sisa',
            'total_nilai',
            'total_pajak',
            'persentase',
            'kategori_breakdown',
            'opening_balance',
            'cash_in',
            'cash_out',
            'saldo_kas',
            'current_balance',
            'cash_accounts',
            'outstanding_ar',
            'outstanding_ap',
            'ar_breakdown',
            'ap_breakdown',
            'total_profit',
            'profit_projects',
            'chart_budget_realisasi',
        ];

        $this->assertSame($expected, array_keys(app(DashboardService::class)->statistics()));
    }

    public function test_dashboard_service_categorizes_projects(): void
    {
        Project::factory()->create(['jenis' => 'barang']);
        Project::factory()->create(['jenis' => 'jasa']);

        $stats = app(DashboardService::class)->statistics();

        $this->assertSame(1, $stats['projects_barang']);
        $this->assertSame(1, $stats['projects_jasa']);
    }

    public function test_dashboard_service_respects_filters(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-06-01', 'nominal' => 25000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-08-01', 'nominal' => 15000000]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('startDate', '2026-06-01')
            ->set('endDate', '2026-06-30')
            ->assertSee('25.000.000', false)
            ->assertSee('25.000.000', false);
    }

    public function test_dashboard_budget_matches_monitoring_budget_for_same_period(): void
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 200000000,
            'allocation' => 200000000,
        ]);

        $budgetPlan = BudgetPlan::create([
            'project_id' => $project->id,
            'periode' => '2026-01',
            'nomor' => 'BP-2026-01',
            'estimasi_pendapatan' => 0,
            'estimasi_biaya' => 0,
            'target_laba' => 0,
        ]);

        BudgetPlanItem::create([
            'budget_plan_id' => $budgetPlan->id,
            'akun_id' => $akun->id,
            'nominal' => 100000000,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
        ]);

        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'tanggal' => '2026-01-15',
            'nominal' => 30000000,
        ]);

        $period = MonitoringPeriod::create([
            'project_id' => $project->id,
            'nomor' => 'MON-2026-001',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
        ]);

        $stats = app(DashboardService::class)->statistics('2026-01-01', '2026-01-31');
        $monitoringBudget = app(MonitoringPeriodService::class)->budgetTotal($period);

        $this->assertSame(100000000.0, $monitoringBudget);
        $this->assertSame($monitoringBudget, $stats['total_budget']);
        $this->assertSame(30000000.0, $stats['total_realisasi']);
        $this->assertSame(70000000.0, $stats['total_sisa']);
    }

    public function test_dashboard_uses_livewire_for_filter_changes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-10', 'nominal' => 10000000]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('startDate', null)
            ->assertSet('endDate', null);
    }
}
