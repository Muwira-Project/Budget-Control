<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_variance_is_budget_minus_realisasi(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $allocation = ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 80000000,
        ]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'nominal' => 30000000]);

        $fresh = $allocation->fresh();

        $this->assertSame(30000000.0, $fresh->total_realisasi);
        $this->assertSame(70000000.0, $fresh->variance);
        $this->assertSame(50000000.0, $fresh->remaining_allocation);
        $this->assertSame(20000000.0, $fresh->available_budget);
    }

    public function test_project_totals_aggregate_allocations_and_realisasi(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();

        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'budget' => 100000000, 'allocation' => 80000000]);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'budget' => 50000000, 'allocation' => 50000000]);

        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'nominal' => 30000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'nominal' => 20000000]);

        $fresh = $project->fresh();

        $this->assertSame(150000000.0, $fresh->total_budget);
        $this->assertSame(130000000.0, $fresh->total_allocation);
        $this->assertSame(50000000.0, $fresh->total_realisasi);
    }

    public function test_realisasi_without_allocation_is_not_counted_in_variance(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $allocation = ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000]);

        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'nominal' => 40000000]);

        $this->assertSame(40000000.0, $allocation->fresh()->total_realisasi);
        $this->assertSame(60000000.0, $allocation->fresh()->variance);
    }
}
