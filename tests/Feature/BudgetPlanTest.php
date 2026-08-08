<?php

namespace Tests\Feature;

use App\Livewire\BudgetPlans\Create as CreateBudgetPlan;
use App\Livewire\BudgetPlans\Edit as EditBudgetPlan;
use App\Livewire\BudgetPlans\Index as IndexBudgetPlan;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('budget-plans.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create();
        BudgetPlan::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user)
            ->get(route('budget-plans.index'))
            ->assertOk();
    }

    public function test_budget_plan_can_be_created_with_items(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-08')
            ->set('estimasiPendapatan', '500000000')
            ->set('targetLaba', '200000000')
            ->set('items', [
                ['akun_id' => $akunA->id, 'nominal' => '120000000'],
                ['akun_id' => $akunB->id, 'nominal' => '80000000'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('budget-plans.index'));

        $this->assertDatabaseHas('budget_plans', [
            'project_id' => $project->id,
            'periode' => '2026-08',
            'estimasi_pendapatan' => 500000000,
            'estimasi_biaya' => 200000000,
            'target_laba' => 200000000,
        ]);
        $this->assertDatabaseHas('budget_plan_items', ['akun_id' => $akunA->id, 'nominal' => 120000000]);
        $this->assertDatabaseHas('budget_plan_items', ['akun_id' => $akunB->id, 'nominal' => 80000000]);
    }

    public function test_budget_plan_estimasi_biaya_is_sum_of_items(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-09')
            ->set('estimasiPendapatan', '300000000')
            ->set('items', [
                ['akun_id' => $akunA->id, 'nominal' => '75000000'],
                ['akun_id' => $akunB->id, 'nominal' => '25000000'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $plan = BudgetPlan::where('project_id', $project->id)->first();
        $this->assertSame(100000000.0, (float) $plan->estimasi_biaya);
    }

    public function test_budget_plan_without_target_laba_stores_null(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-10')
            ->set('estimasiPendapatan', '300000000')
            ->set('targetLaba', '')
            ->set('items', [['akun_id' => $akun->id, 'nominal' => '100000000']])
            ->call('save')
            ->assertHasNoErrors();

        $plan = BudgetPlan::where('project_id', $project->id)->first();

        $this->assertSame(0.0, (float) $plan->target_laba);
    }

    public function test_budget_plan_target_laba_may_be_negative(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-10')
            ->set('estimasiPendapatan', '100000000')
            ->set('targetLaba', '-25000000')
            ->set('items', [['akun_id' => $akun->id, 'nominal' => '125000000']])
            ->call('save')
            ->assertHasNoErrors();

        $plan = BudgetPlan::where('project_id', $project->id)->first();

        $this->assertSame(-25000000.0, (float) $plan->target_laba);
    }

    public function test_budget_plan_saran_can_be_negative(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-11')
            ->set('estimasiPendapatan', '100000000')
            ->set('items', [['akun_id' => $akun->id, 'nominal' => '150000000']])
            ->assertSee('Rp -50.000.000');
    }

    public function test_budget_plan_periode_must_be_valid(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-13')
            ->set('estimasiPendapatan', '500000000')
            ->set('items', [['akun_id' => $akun->id, 'nominal' => '100000000']])
            ->call('save')
            ->assertHasErrors(['periode']);
    }

    public function test_budget_plan_requires_at_least_one_item(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-08')
            ->set('estimasiPendapatan', '500000000')
            ->set('items', [])
            ->call('save')
            ->assertHasErrors(['items']);
    }

    public function test_budget_plan_akun_must_be_distinct(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-08')
            ->set('estimasiPendapatan', '500000000')
            ->set('items', [
                ['akun_id' => $akun->id, 'nominal' => '50000000'],
                ['akun_id' => $akun->id, 'nominal' => '30000000'],
            ])
            ->call('save')
            ->assertHasErrors(['items.1.akun_id']);
    }

    public function test_budget_plan_duplicate_project_periode_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-08']);

        Livewire::actingAs($user)
            ->test(CreateBudgetPlan::class)
            ->set('projectId', $project->id)
            ->set('periode', '2026-08')
            ->set('estimasiPendapatan', '500000000')
            ->set('items', [['akun_id' => $akun->id, 'nominal' => '100000000']])
            ->call('save')
            ->assertHasErrors(['periode']);
    }

    public function test_budget_plan_can_be_updated(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();
        $plan = BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-08']);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 50000000]);

        Livewire::actingAs($user)
            ->test(EditBudgetPlan::class, ['budgetPlan' => $plan])
            ->set('estimasiPendapatan', '600000000')
            ->set('items', [
                ['akun_id' => $akunB->id, 'nominal' => '150000000'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('budget-plans.index'));

        $this->assertDatabaseHas('budget_plans', [
            'id' => $plan->id,
            'estimasi_pendapatan' => 600000000,
            'estimasi_biaya' => 150000000,
        ]);
        $this->assertDatabaseMissing('budget_plan_items', ['akun_id' => $akunA->id]);
        $this->assertDatabaseHas('budget_plan_items', ['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 150000000]);
    }

    public function test_budget_plan_can_be_deleted_with_items(): void
    {
        $user = User::factory()->create();
        $plan = BudgetPlan::factory()->create();
        $item = BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id]);

        Livewire::actingAs($user)
            ->test(IndexBudgetPlan::class)
            ->call('delete', $plan->id);

        $this->assertDatabaseMissing('budget_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('budget_plan_items', ['id' => $item->id]);
    }

    public function test_budget_plan_index_filters_by_project(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create(['nama' => 'Project Alpha']);
        $projectB = Project::factory()->create(['nama' => 'Project Beta']);
        BudgetPlan::factory()->create(['project_id' => $projectA->id, 'periode' => '2026-08']);
        BudgetPlan::factory()->create(['project_id' => $projectB->id, 'periode' => '2026-09']);

        Livewire::actingAs($user)
            ->test(IndexBudgetPlan::class)
            ->set('projectId', $projectA->id)
            ->assertSee('2026-08')
            ->assertDontSee('2026-09');
    }

    public function test_budget_plan_total_budget_sums_items(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        $plan = BudgetPlan::create([
            'project_id' => $project->id,
            'periode' => '2026-08',
            'estimasi_pendapatan' => 500000000,
            'estimasi_biaya' => 300000000,
            'target_laba' => 200000000,
        ]);

        BudgetPlanItem::create(['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 120000000]);
        BudgetPlanItem::create(['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 80000000]);

        $this->assertSame(2, $plan->items()->count());
        $this->assertSame(200000000.0, $plan->fresh()->total_budget);
    }

    public function test_plan_can_generate_draft_allocations(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();
        $plan = BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-08']);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 120000000]);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 80000000]);

        Livewire::actingAs($user)
            ->test(IndexBudgetPlan::class)
            ->call('createAllocations', $plan->id);

        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akunA->id,
            'budget' => 120000000,
            'allocation' => 120000000,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akunB->id,
            'budget' => 80000000,
            'allocation' => 80000000,
            'status' => 'draft',
        ]);

        Livewire::actingAs($user)
            ->test(IndexBudgetPlan::class)
            ->call('createAllocations', $plan->id);

        $this->assertSame(2, ProjectAkun::where('project_id', $project->id)->count());
    }
}
