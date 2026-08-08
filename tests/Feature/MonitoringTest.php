<?php

namespace Tests\Feature;

use App\Livewire\Monitoring\Create as CreateMonitoring;
use App\Livewire\Monitoring\Edit as EditMonitoring;
use App\Livewire\Monitoring\Index as IndexMonitoring;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MonitoringPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('monitoring.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        MonitoringPeriod::factory()->create();

        $this->actingAs($user)
            ->get(route('monitoring.index'))
            ->assertOk()
            ->assertSee('Monitoring');
    }

    public function test_period_can_be_created(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateMonitoring::class)
            ->set('projectId', $project->id)
            ->set('tanggalMulai', '2026-03-01')
            ->set('tanggalSelesai', '2026-03-14')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('monitoring.index'));

        $period = MonitoringPeriod::where('project_id', $project->id)->first();
        $this->assertNotNull($period);
        $this->assertSame('2026-03-01', $period->tanggal_mulai->format('Y-m-d'));
        $this->assertSame('2026-03-14', $period->tanggal_selesai->format('Y-m-d'));
        $this->assertMatchesRegularExpression('/^MON-\d{4}-\d{3}$/', $period->nomor);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateMonitoring::class)
            ->set('tanggalMulai', '2026-03-14')
            ->set('tanggalSelesai', '2026-03-01')
            ->call('save')
            ->assertHasErrors(['tanggal_selesai']);
    }

    public function test_period_can_be_updated(): void
    {
        $user = User::factory()->create();
        $period = MonitoringPeriod::factory()->create();

        Livewire::actingAs($user)
            ->test(EditMonitoring::class, ['monitoringPeriod' => $period])
            ->set('tanggalMulai', '2026-04-01')
            ->set('tanggalSelesai', '2026-04-20')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('monitoring.index'));

        $this->assertSame('2026-04-01', $period->fresh()->tanggal_mulai->format('Y-m-d'));
        $this->assertSame('2026-04-20', $period->fresh()->tanggal_selesai->format('Y-m-d'));
    }

    public function test_period_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $period = MonitoringPeriod::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexMonitoring::class)
            ->call('delete', $period->id);

        $this->assertDatabaseMissing('monitoring_periods', ['id' => $period->id]);
    }

    public function test_resume_page_shows_budget_actual_and_variance(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        $plan = BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-03']);
        BudgetPlanItem::factory()->create([
            'budget_plan_id' => $plan->id,
            'akun_id' => $akun->id,
            'nominal' => 80000000,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-31',
        ]);

        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => Vendor::factory()->create()->id,
            'supplier_id' => null,
            'tanggal' => '2026-03-05',
            'nominal' => 30000000,
        ]);

        $period = MonitoringPeriod::factory()->create([
            'project_id' => $project->id,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-14',
        ]);

        $this->actingAs($user)
            ->get(route('monitoring.show', $period))
            ->assertOk()
            ->assertSee('Period Resume');

        $service = app(MonitoringPeriodService::class);
        $this->assertSame(80000000.0, $service->budgetTotal($period));
        $this->assertSame(30000000.0, $service->actualTotal($period));

        $row = $service->accountBreakdown($period)->first();
        $this->assertSame(80000000.0, $row['budget']);
        $this->assertSame(30000000.0, $row['actual']);
        $this->assertSame(50000000.0, $row['variance']);
    }

    public function test_week_and_month_are_derived_from_start_date(): void
    {
        $period = MonitoringPeriod::factory()->create([
            'tanggal_mulai' => '2026-03-08',
            'tanggal_selesai' => '2026-03-21',
        ]);

        $this->assertSame(2, $period->week);
        $this->assertSame('March 2026', $period->month);
        $this->assertSame('8 Mar 2026 – 21 Mar 2026', $period->periode_label);
    }

    public function test_legacy_budget_item_is_counted_only_in_first_period_of_month(): void
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $plan = BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-03']);
        BudgetPlanItem::factory()->create([
            'budget_plan_id' => $plan->id,
            'akun_id' => $akun->id,
            'nominal' => 40000000,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
        ]);

        $first = MonitoringPeriod::factory()->create([
            'project_id' => $project->id,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-07',
        ]);
        $second = MonitoringPeriod::factory()->create([
            'project_id' => $project->id,
            'tanggal_mulai' => '2026-03-08',
            'tanggal_selesai' => '2026-03-14',
        ]);

        $service = app(MonitoringPeriodService::class);

        $this->assertSame(40000000.0, $service->budgetTotal($first));
        $this->assertSame(0.0, $service->budgetTotal($second));
    }
}
