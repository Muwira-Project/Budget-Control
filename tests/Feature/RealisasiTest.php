<?php

namespace Tests\Feature;

use App\Livewire\Realisasi\Create as CreateRealisasi;
use App\Livewire\Realisasi\Edit as EditRealisasi;
use App\Livewire\Realisasi\Index as IndexRealisasi;
use App\Models\Akun;
use App\Models\Kategori;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
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
    private function allocatedAkun(Project $project, int $budget = 100000000): Akun
    {
        $akun = Akun::factory()->create();

        ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => $budget,
            'allocation' => $budget,
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
            ->assertOk();
    }

    public function test_realisasi_can_be_created(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $vendor = Vendor::factory()->create();
        $kategori = Kategori::factory()->create(['nama' => 'Material']);

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('kategoriId', $kategori->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '25000000')
            ->set('keterangan', 'Pembayaran material tahap 1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('realisasi.index'));

        $this->assertDatabaseHas('realisasi', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'kategori_id' => $kategori->id,
        ]);
    }

    public function test_realisasi_requires_a_vendor_or_supplier(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '25000000')
            ->call('save')
            ->assertHasErrors(['vendor_id']);
    }

    public function test_realisasi_can_be_created_with_supplier(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $supplier = Supplier::factory()->create();
        $kategori = Kategori::factory()->create(['nama' => 'Material']);

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('pihakJenis', 'supplier')
            ->set('supplierId', $supplier->id)
            ->set('kategoriId', $kategori->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '25000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('realisasi.index'));

        $this->assertDatabaseHas('realisasi', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => null,
            'supplier_id' => $supplier->id,
            'kategori_id' => $kategori->id,
        ]);
    }

    public function test_realisasi_akun_must_be_allocated_to_selected_project(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $akunA = $this->allocatedAkun($projectA);

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $projectB->id)
            ->set('akunId', $akunA->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '10000')
            ->call('save')
            ->assertHasErrors(['akun_id']);
    }

    public function test_realisasi_can_be_updated(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $vendor = Vendor::factory()->create();
        $kategori = Kategori::factory()->create(['nama' => 'Jasa']);
        $realisasi = Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id]);

        Livewire::actingAs($user)
            ->test(EditRealisasi::class, ['realisasi' => $realisasi])
            ->set('vendorId', $vendor->id)
            ->set('kategoriId', $kategori->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('realisasi.index'));

        $this->assertDatabaseHas('realisasi', ['id' => $realisasi->id, 'vendor_id' => $vendor->id, 'kategori_id' => $kategori->id]);
    }

    public function test_realisasi_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $realisasi = Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id]);

        Livewire::actingAs($user)
            ->test(IndexRealisasi::class)
            ->call('delete', $realisasi->id);

        $this->assertDatabaseMissing('realisasi', ['id' => $realisasi->id]);
    }

    public function test_realisasi_index_uses_eager_loading(): void
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
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 extra query is the global notification bell (constant, not per row).
        $this->assertLessThanOrEqual(10, $queryCount);
    }

    public function test_realisasi_index_filters_by_date_range(): void
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

    public function test_realisasi_index_filters_by_start_date_only(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-10', 'keterangan' => 'Pembayaran Januari']);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-03-10', 'keterangan' => 'Pembayaran Maret']);

        Livewire::actingAs($user)
            ->test(IndexRealisasi::class)
            ->set('startDate', '2026-03-01')
            ->assertSee('Pembayaran Maret')
            ->assertDontSee('Pembayaran Januari');
    }

    public function test_realisasi_invalid_date_range_is_handled(): void
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
