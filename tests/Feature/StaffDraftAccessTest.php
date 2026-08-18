<?php

namespace Tests\Feature;

use App\Livewire\Allokasis\Index as AllocationIndex;
use App\Livewire\Cashflows\Index;
use App\Models\Akun;
use App\Models\Cashflow;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffDraftAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_admin_modules(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('projects.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.profit-loss'))->assertForbidden();
        $this->actingAs($staff)->get(route('budget-plans.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('trash.index'))->assertForbidden();
    }

    public function test_staff_can_access_operator_modules(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('cashflows.index'))->assertOk();
        $this->actingAs($staff)->get(route('cashflows.create'))->assertOk();
        $this->actingAs($staff)->get(route('receivables.index'))->assertOk();
        $this->actingAs($staff)->get(route('receivables.create'))->assertOk();
        $this->actingAs($staff)->get(route('payables.index'))->assertOk();
        $this->actingAs($staff)->get(route('payables.create'))->assertOk();
        $this->actingAs($staff)->get(route('ar-ap.index'))->assertOk();
        $this->actingAs($staff)->get(route('monitoring.index'))->assertOk();
    }

    public function test_staff_cannot_submit_another_users_draft(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $allocation = ProjectAkun::create([
            'project_id' => Project::factory()->create()->id,
            'akun_id' => Akun::factory()->create()->id,
            'budget' => 100000,
            'allocation' => 100000,
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        Livewire::actingAs($staff)
            ->test(AllocationIndex::class)
            ->call('submit', $allocation->id);

        $this->assertSame('draft', $allocation->fresh()->status->value);
    }

    public function test_staff_sees_aggregate_summary_not_detail(): void
    {
        $staff = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        Realisasi::factory()->count(3)->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
        ]);

        $this->actingAs($staff)
            ->get(route('realisasi.index'))
            ->assertRedirect(route('realisasi.summary'));

        $this->actingAs($staff)
            ->get(route('realisasi.summary'))
            ->assertOk()
            ->assertSee('Ringkasan realisasi');
    }

    public function test_staff_cannot_edit_receivables_or_payables(): void
    {
        $staff = User::factory()->create();
        $receivable = Receivable::factory()->create();
        $payable = Payable::factory()->create();

        $this->actingAs($staff)->get(route('receivables.edit', $receivable))->assertForbidden();
        $this->actingAs($staff)->get(route('payables.edit', $payable))->assertForbidden();
    }

    public function test_staff_cannot_submit_another_users_cashflow_draft(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $draft = Cashflow::factory()->create([
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->call('submit', $draft->id);

        $this->assertSame('draft', $draft->fresh()->status->value);
    }
}
