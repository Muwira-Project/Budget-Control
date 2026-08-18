<?php

namespace Tests\Feature;

use App\Enums\AllocationStatus;
use App\Enums\UserRole;
use App\Models\Akun;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => UserRole::Staff]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function allocation(array $overrides = []): ProjectAkun
    {
        return ProjectAkun::create(array_merge([
            'project_id' => Project::factory()->create()->id,
            'akun_id' => Akun::factory()->create()->id,
            'budget' => 1000,
            'allocation' => 500,
            'status' => AllocationStatus::Draft,
        ], $overrides));
    }

    public function test_staff_cannot_access_approvals_page(): void
    {
        $this->actingAs($this->staff());

        $this->get('/approvals')->assertForbidden();
    }

    public function test_staff_cannot_access_backup_page(): void
    {
        $this->actingAs($this->staff());

        $this->get('/backup')->assertForbidden();
    }

    public function test_staff_cannot_access_users_page(): void
    {
        $this->actingAs($this->staff());

        $this->get('/users')->assertForbidden();
    }

    public function test_staff_cannot_create_monitoring_period(): void
    {
        $this->actingAs($this->staff());

        $this->get('/monitoring/create')->assertForbidden();
    }

    public function test_staff_cannot_edit_monitoring_period(): void
    {
        $period = MonitoringPeriod::factory()->create();
        $this->actingAs($this->staff());

        $this->get('/monitoring/'.$period->id.'/edit')->assertForbidden();
    }

    public function test_staff_can_view_own_draft_allocation(): void
    {
        $staff = $this->staff();
        $allocation = $this->allocation(['created_by' => $staff->id]);

        $this->actingAs($staff);

        $this->get('/allokasis/'.$allocation->id.'/edit')->assertOk();
    }

    public function test_staff_cannot_edit_others_draft_allocation(): void
    {
        $otherStaff = $this->staff();
        $allocation = $this->allocation(['created_by' => $otherStaff->id]);

        $this->actingAs($this->staff());

        $this->get('/allokasis/'.$allocation->id.'/edit')->assertForbidden();
    }

    public function test_staff_cannot_edit_own_non_draft_allocation(): void
    {
        $staff = $this->staff();
        $allocation = $this->allocation([
            'created_by' => $staff->id,
            'status' => AllocationStatus::Waiting,
        ]);

        $this->actingAs($staff);

        $this->get('/allokasis/'.$allocation->id.'/edit')->assertForbidden();
    }

    public function test_admin_can_access_all_admin_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get('/approvals')->assertOk();
        $this->get('/backup')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/users/create')->assertOk();
        $this->get('/monitoring/create')->assertOk();
    }

    public function test_gate_allows_admin_for_any_admin_ability(): void
    {
        $this->actingAs($this->admin());

        $this->assertTrue(Gate::allows('manageUsers', User::class));
        $this->assertTrue(Gate::allows('accessBackups', User::class));
        $this->assertTrue(Gate::allows('manageMonitoring', MonitoringPeriod::class));
        $this->assertTrue(Gate::allows('manageSettlements', User::class));
        $this->assertTrue(Gate::allows('releaseReceivables', User::class));
    }

    public function test_gate_denies_staff_for_admin_abilities(): void
    {
        $this->actingAs($this->staff());

        $this->assertFalse(Gate::allows('manageUsers', User::class));
        $this->assertFalse(Gate::allows('accessBackups', User::class));
        $this->assertFalse(Gate::allows('manageMonitoring', MonitoringPeriod::class));
        $this->assertFalse(Gate::allows('manageSettlements', User::class));
        $this->assertFalse(Gate::allows('releaseReceivables', User::class));
    }
}
