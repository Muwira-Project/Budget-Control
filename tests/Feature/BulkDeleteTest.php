<?php

namespace Tests\Feature;

use App\Livewire\Akuns\Index as AkunIndex;
use App\Livewire\Allokasis\Index as AllokasiIndex;
use App\Livewire\Vendors\Index as VendorIndex;
use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_removes_selected_akuns(): void
    {
        $user = User::factory()->create();
        $a = Akun::factory()->create();
        $b = Akun::factory()->create();
        Akun::factory()->create();

        $component = Livewire::actingAs($user)->test(AkunIndex::class);
        $component->set('selectedIds', [$a->id, $b->id]);
        $component->call('deleteSelected');

        $this->assertDatabaseMissing('akuns', ['id' => $a->id]);
        $this->assertDatabaseMissing('akuns', ['id' => $b->id]);
        $this->assertDatabaseCount('akuns', 1);
        $component->assertSet('selectedIds', []);
    }

    public function test_select_all_visible_selects_the_current_page(): void
    {
        $user = User::factory()->admin()->create();
        $accounts = Akun::factory()->count(3)->create();

        Livewire::actingAs($user)
            ->test(AkunIndex::class)
            ->call('toggleAllVisible')
            ->assertSet('selectedIds', Akun::query()->orderBy('jenis_akun')->orderBy('kode_akun')->pluck('id')->all());
    }

    public function test_bulk_delete_skips_approved_allocations(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akunA = Akun::factory()->create();
        $akunB = Akun::factory()->create();
        $approved = ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunA->id, 'budget' => 100, 'allocation' => 100, 'status' => 'approved']);
        $draft = ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akunB->id, 'budget' => 200, 'allocation' => 200, 'status' => 'draft', 'created_by' => $user->id]);

        $component = Livewire::actingAs($user)->test(AllokasiIndex::class);
        $component->set('selectedIds', [$approved->id, $draft->id]);
        $component->call('deleteSelected');

        $this->assertDatabaseHas('project_akuns', ['id' => $approved->id]);
        $this->assertDatabaseMissing('project_akuns', ['id' => $draft->id]);
    }

    public function test_bulk_delete_skips_vendors_still_in_use(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $used = Vendor::factory()->create();
        $free = Vendor::factory()->create();
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'vendor_id' => $used->id]);

        $component = Livewire::actingAs($user)->test(VendorIndex::class);
        $component->set('selectedIds', [$used->id, $free->id]);
        $component->call('deleteSelected');

        $this->assertDatabaseHas('vendors', ['id' => $used->id]);
        $this->assertDatabaseMissing('vendors', ['id' => $free->id]);
    }
}
