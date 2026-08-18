<?php

namespace Tests\Feature;

use App\Livewire\Realisasi\Index as IndexRealisasi;
use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RealisasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a master akun allocated to the given project.
     */
    private function allocatedAkun(Project $project): Akun
    {
        $akun = Akun::factory()->create();

        ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 100000000,
        ]);

        return $akun;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('realisasi.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id]);

        $this->actingAs($user)
            ->get(route('realisasi.index'))
            ->assertRedirect(route('realisasi.detail'));

        $this->actingAs($user)
            ->get(route('realisasi.detail'))
            ->assertOk()
            ->assertDontSee('Add Actual');
    }

    public function test_manual_create_page_is_not_available(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/realisasi/create')
            ->assertNotFound();
    }

    public function test_index_uses_eager_loading(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);

        foreach (range(1, 10) as $_) {
            Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('realisasi.index'))
            ->assertRedirect(route('realisasi.detail'));

        $this->actingAs($user)
            ->get(route('realisasi.detail'))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 extra query is the global notification bell (constant, not per row).
        $this->assertLessThanOrEqual(10, $queryCount);
    }

    public function test_index_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-10', 'keterangan' => 'Pembayaran Januari']);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-03-10', 'keterangan' => 'Pembayaran Maret']);

        Livewire::actingAs($user)
            ->test(IndexRealisasi::class)
            ->set('startDate', '2026-01-01')
            ->set('endDate', '2026-01-31')
            ->assertSee('Pembayaran Januari')
            ->assertDontSee('Pembayaran Maret');
    }

    public function test_index_invalid_date_range_is_handled(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-10', 'keterangan' => 'Pembayaran Januari']);

        Livewire::actingAs($user)
            ->test(IndexRealisasi::class)
            ->set('startDate', '2026-02-01')
            ->set('endDate', '2026-01-31')
            ->assertSee('Invalid date range')
            ->assertDontSee('Pembayaran Januari');
    }
}
