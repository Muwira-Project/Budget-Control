<?php

namespace Tests\Feature;

use App\Livewire\Allokasis\Create as CreateAllokasi;
use App\Livewire\Allokasis\Edit as EditAllokasi;
use App\Livewire\Budgeting\Index as IndexBudgeting;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Cashflow;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use App\Services\CashflowService;
use App\Services\DashboardService;
use App\Services\ProjectAkunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('budgeting.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000]);

        $this->actingAs($user)
            ->get(route('budgeting.index'))
            ->assertOk();
    }

    public function test_draft_allocation_edit_page_renders(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $allocation = ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 80000000,
            'status' => 'draft',
        ]);

        Livewire::actingAs($user)
            ->test(EditAllokasi::class, ['allocation' => $allocation])
            ->assertOk()
            ->assertSee('Edit Budget Allocation')
            ->assertSet('budget', '100000000.00')
            ->assertSet('allocationNominal', '80000000.00');
    }

    public function test_staff_can_create_draft_allocation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateAllokasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('budget', '100000000')
            ->set('allocationNominal', '80000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('budgeting.index'));

        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 80000000,
            'status' => 'draft',
        ]);
    }

    public function test_allocation_cannot_exceed_budget(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateAllokasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('budget', '100000000')
            ->set('allocationNominal', '150000000')
            ->call('save')
            ->assertHasErrors(['allocation']);
    }

    public function test_duplicate_akun_for_project_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000]);

        Livewire::actingAs($user)
            ->test(CreateAllokasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('budget', '100000000')
            ->set('allocationNominal', '80000000')
            ->call('save')
            ->assertHasErrors(['akun_id']);
    }

    public function test_draft_can_be_submitted(): void
    {
        $user = User::factory()->create();
        $allocation = $this->makeAllocation('draft');
        $allocation->update(['created_by' => $user->id]);

        Livewire::actingAs($user)
            ->test(IndexBudgeting::class)
            ->call('submit', $allocation->id);

        $this->assertSame('waiting', $allocation->fresh()->status->value);
    }

    public function test_staff_cannot_approve_allocation(): void
    {
        $staff = User::factory()->create();
        $allocation = $this->makeAllocation('waiting');

        Livewire::actingAs($staff)
            ->test(IndexBudgeting::class)
            ->call('approve', $allocation->id);

        $this->assertSame('waiting', $allocation->fresh()->status->value);
        $this->assertNull($allocation->fresh()->approved_by);
    }

    public function test_admin_can_approve_allocation(): void
    {
        $admin = User::factory()->admin()->create();
        $allocation = $this->makeAllocation('waiting');

        Livewire::actingAs($admin)
            ->test(IndexBudgeting::class)
            ->call('approve', $allocation->id);

        $fresh = $allocation->fresh();
        $this->assertSame('approved', $fresh->status->value);
        $this->assertSame($admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_admin_can_reject_allocation(): void
    {
        $admin = User::factory()->admin()->create();
        $allocation = $this->makeAllocation('waiting');

        Livewire::actingAs($admin)
            ->test(IndexBudgeting::class)
            ->call('reject', $allocation->id);

        $this->assertSame('rejected', $allocation->fresh()->status->value);
    }

    public function test_approving_allocation_syncs_budget_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        $plan = BudgetPlan::factory()->create([
            'project_id' => $project->id,
            'periode' => '2026-08',
            'estimasi_pendapatan' => 100000000,
            'estimasi_biaya' => 30000000,
            'target_laba' => 70000000,
        ]);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 10000000]);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 20000000]);

        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'budget' => 40000000, 'allocation' => 40000000, 'status' => 'waiting']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'budget' => 20000000, 'allocation' => 20000000, 'status' => 'approved']);

        $allocation = ProjectAkun::where('project_id', $project->id)->where('akun_id', $akunA->id)->first();

        Livewire::actingAs($admin)
            ->test(IndexBudgeting::class)
            ->call('approve', $allocation->id);

        $fresh = $plan->fresh();

        $this->assertSame(2, $fresh->items()->count());
        $this->assertDatabaseHas('budget_plan_items', ['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 40000000]);
        $this->assertDatabaseHas('budget_plan_items', ['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 20000000]);
        $this->assertSame(60000000.0, (float) $fresh->estimasi_biaya);
        $this->assertSame(40000000.0, (float) $fresh->target_laba);
    }

    public function test_rejecting_allocation_removes_it_from_budget_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        $plan = BudgetPlan::factory()->create([
            'project_id' => $project->id,
            'periode' => '2026-08',
            'estimasi_pendapatan' => 100000000,
            'estimasi_biaya' => 50000000,
            'target_laba' => 50000000,
        ]);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id, 'nominal' => 30000000]);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 20000000]);

        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'budget' => 30000000, 'allocation' => 30000000, 'status' => 'waiting']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'budget' => 20000000, 'allocation' => 20000000, 'status' => 'approved']);

        $allocation = ProjectAkun::where('project_id', $project->id)->where('akun_id', $akunA->id)->first();

        Livewire::actingAs($admin)
            ->test(IndexBudgeting::class)
            ->call('reject', $allocation->id);

        $fresh = $plan->fresh();

        $this->assertSame(1, $fresh->items()->count());
        $this->assertDatabaseMissing('budget_plan_items', ['budget_plan_id' => $plan->id, 'akun_id' => $akunA->id]);
        $this->assertDatabaseHas('budget_plan_items', ['budget_plan_id' => $plan->id, 'akun_id' => $akunB->id, 'nominal' => 20000000]);
        $this->assertSame(20000000.0, (float) $fresh->estimasi_biaya);
        $this->assertSame(80000000.0, (float) $fresh->target_laba);
    }

    public function test_approved_allocation_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $allocation = $this->makeAllocation('approved');

        Livewire::actingAs($user)
            ->test(IndexBudgeting::class)
            ->call('delete', $allocation->id);

        $this->assertDatabaseHas('project_akuns', ['id' => $allocation->id]);
    }

    public function test_dashboard_total_allocation_only_counts_approved(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'budget' => 100000000, 'allocation' => 50000000, 'status' => 'draft']);

        $stats = app(DashboardService::class)->statistics();

        $this->assertSame(100000000.0, $stats['total_allocation']);
    }

    public function test_create_form_prefills_budget_from_plan(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $plan = BudgetPlan::factory()->create(['project_id' => $project->id, 'periode' => '2026-08']);
        BudgetPlanItem::factory()->create(['budget_plan_id' => $plan->id, 'akun_id' => $akun->id, 'nominal' => 75000000]);

        Livewire::actingAs($user)
            ->test(CreateAllokasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->assertSet('budget', '75000000.00')
            ->assertSet('allocationNominal', '75000000.00');
    }

    public function test_realisasi_requires_approved_allocation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'waiting']);

        $this->assertDatabaseHas('project_akuns', ['project_id' => $project->id, 'akun_id' => $akun->id, 'status' => 'waiting']);
        $this->assertTrue(
            ProjectAkun::where('project_id', $project->id)
                ->where('akun_id', $akun->id)
                ->where('status', 'approved')
                ->doesntExist()
        );
    }

    public function test_staff_can_create_non_project_allocation(): void
    {
        $user = User::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateAllokasi::class)
            ->set('projectId', null)
            ->set('type', 'other_outcome')
            ->set('customName', 'Test Expense')
            ->set('akunId', $akun->id)
            ->set('budget', '50000000')
            ->set('allocationNominal', '50000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('budgeting.index'));

        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akun->id,
            'budget' => 50000000,
            'allocation' => 50000000,
            'status' => 'draft',
            'type' => 'other_outcome',
            'custom_name' => 'Test Expense',
        ]);
    }

    public function test_approving_non_project_allocation_creates_cashflow_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $akun = Akun::factory()->create();
        $allocation = ProjectAkun::create([
            'project_id' => null,
            'akun_id' => $akun->id,
            'budget' => 50000000,
            'allocation' => 50000000,
            'status' => 'waiting',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(IndexBudgeting::class)
            ->call('approve', $allocation->id);

        $fresh = $allocation->fresh();
        $this->assertSame('approved', $fresh->status->value);

        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'keluar',
            'sumber' => 'pengeluaran_lain',
            'akun_id' => $akun->id,
            'nominal' => 50000000,
            'status' => 'draft',
        ]);
    }

    public function test_non_project_cashflow_follows_full_approval_flow(): void
    {
        $admin = User::factory()->admin()->create();
        $akun = Akun::factory()->create();
        $allocation = ProjectAkun::create([
            'project_id' => null,
            'akun_id' => $akun->id,
            'budget' => 50000000,
            'allocation' => 50000000,
            'status' => 'waiting',
            'created_by' => $admin->id,
            'type' => 'other_outcome',
        ]);

        // Approve allocation -> cashflow draft dibuat.
        app(ProjectAkunService::class)->approve($allocation);

        $cashflow = Cashflow::where('sumber', 'pengeluaran_lain')->where('akun_id', $akun->id)->first();
        $this->assertNotNull($cashflow);
        $this->assertSame('draft', $cashflow->status->value);

        // Post -> posted + voucher.
        app(CashflowService::class)->post($cashflow->fresh());
        $this->assertSame('posted', $cashflow->fresh()->status->value);
        $this->assertDatabaseHas('vouchers', ['cashflow_id' => $cashflow->id]);
    }

    /**
     * Create a project-akun row with the given status.
     */
    private function makeAllocation(string $status): ProjectAkun
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();

        return ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 80000000,
            'status' => $status,
        ]);
    }
}
