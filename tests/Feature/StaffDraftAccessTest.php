<?php

namespace Tests\Feature;

use App\Livewire\Allokasis\Index as AllocationIndex;
use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
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
        $this->actingAs($staff)->get(route('payments.index'))->assertForbidden();
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
}
