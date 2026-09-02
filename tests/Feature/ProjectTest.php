<?php

namespace Tests\Feature;

use App\Livewire\Projects\Create as CreateProject;
use App\Livewire\Projects\Edit as EditProject;
use App\Livewire\Projects\Index as IndexProject;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function seedMasterData(): void
    {
        // Create master type for Division (idempotent)
        $divisionType = MasterType::firstOrCreate(
            ['kode' => 'DIVISION'],
            [
                'kode' => 'DIVISION',
                'nama' => 'Division',
                'flag_project' => true,
                'aktif' => true,
                'is_system' => true,
                'sort' => 5,
            ]
        );

        // Create master items for divisions (idempotent)
        MasterItem::firstOrCreate(
            ['master_type_id' => $divisionType->id, 'kode' => 'CONSTRUCTION'],
            [
                'master_type_id' => $divisionType->id,
                'kode' => 'CONSTRUCTION',
                'nama' => 'Construction',
                'aktif' => true,
            ]
        );

        MasterItem::firstOrCreate(
            ['master_type_id' => $divisionType->id, 'kode' => 'CIVIL'],
            [
                'master_type_id' => $divisionType->id,
                'kode' => 'CIVIL',
                'nama' => 'Civil',
                'aktif' => true,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $this->seedMasterData();
        $user = User::factory()->admin()->create();
        Project::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk();
    }

    public function test_project_can_be_created(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('kode', 'PRJ-001')
            ->set('nama', 'Gedung Kantor')
            ->set('lokasi', 'Jakarta')
            ->set('jenis', 'barang')
            ->set('status', 'progress')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['kode' => 'PRJ-001', 'nama' => 'Gedung Kantor']);
    }

    public function test_project_with_empty_optional_fields_stores_null(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('kode', 'PRJ-KOSONG')
            ->set('nama', 'Project Kosong')
            ->set('jenis', 'jasa')
            ->set('qty', '')
            ->set('hargaSatuan', '')
            ->set('pajak', '')
            ->set('tanggalMulai', '')
            ->set('targetSelesai', '')
            ->set('status', 'progress')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $project = Project::where('kode', 'PRJ-KOSONG')->first();

        $this->assertNull($project->qty);
        $this->assertNull($project->harga_satuan);
        $this->assertSame(0.0, (float) $project->pajak);
        $this->assertNull($project->tanggal_mulai);
        $this->assertNull($project->target_selesai);
        $this->assertSame(0.0, (float) $project->nilai_total);
    }

    public function test_project_kode_must_be_unique(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('kode', 'PRJ-001')
            ->set('nama', 'Project Lain')
            ->set('jenis', 'barang')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_project_can_be_updated(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProject::class, ['project' => $project])
            ->set('nama', 'Nama Project Baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'nama' => 'Nama Project Baru']);
    }

    public function test_project_can_be_deleted(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexProject::class)
            ->call('delete', $project->id);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_can_be_searched_by_kode_or_nama(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Gedung Kantor', 'division_id' => MasterItem::where('kode', 'CONSTRUCTION')->first()?->id]);
        Project::factory()->create(['kode' => 'PRJ-002', 'nama' => 'Gudang Logistik', 'division_id' => MasterItem::where('kode', 'CIVIL')->first()?->id]);

        $component = Livewire::actingAs($user)->test(IndexProject::class)
            ->assertSee('Gedung Kantor')
            ->assertSee('Gudang Logistik');

        $component->set('search', 'Gedung')
            ->assertSee('Gedung Kantor')
            ->assertDontSee('Gudang Logistik');

        $component->set('search', 'PRJ-002')
            ->assertSee('Gudang Logistik')
            ->assertDontSee('Gedung Kantor');
    }

    public function test_project_can_be_created_with_jenis_pricing_and_dates(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('kode', 'PRJ-002')
            ->set('nama', 'Gedung Gudang')
            ->set('lokasi', 'Surabaya')
            ->set('jenis', 'jasa')
            ->set('qty', '500')
            ->set('satuan', 'm2')
            ->set('hargaSatuan', '1500000')
            ->set('pajak', '2')
            ->set('tanggalMulai', '2026-09-01')
            ->set('targetSelesai', '2027-03-31')
            ->set('status', 'progress')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $project = Project::where('kode', 'PRJ-002')->firstOrFail();

        $this->assertSame('jasa', $project->jenis->value);
        $this->assertSame(500.0, (float) $project->qty);
        $this->assertSame('m2', $project->satuan);
        $this->assertSame(1500000.0, (float) $project->harga_satuan);
        $this->assertSame(2.0, (float) $project->pajak);
        $this->assertSame('2026-09-01', $project->tanggal_mulai->format('Y-m-d'));
        $this->assertSame('2027-03-31', $project->target_selesai->format('Y-m-d'));
    }

    public function test_pajak_defaults_follow_jenis(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('jenis', 'jasa')
            ->assertSet('pajak', '2')
            ->set('jenis', 'barang')
            ->assertSet('pajak', '11');
    }

    public function test_target_selesai_must_be_after_tanggal_mulai(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('kode', 'PRJ-003')
            ->set('nama', 'Project Invalid')
            ->set('jenis', 'barang')
            ->set('tanggalMulai', '2027-01-01')
            ->set('targetSelesai', '2026-01-01')
            ->call('save')
            ->assertHasErrors(['target_selesai']);
    }
}
