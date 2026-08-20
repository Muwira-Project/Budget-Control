<?php

namespace Tests\Feature;

use App\Livewire\Akuns\Index as AkunIndex;
use App\Livewire\Allokasis\Index as AllokasiIndex;
use App\Livewire\MasterItems\Index as MasterItemIndex;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
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
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $used = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);
        $free = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $used->id,
        ]);

        $component = Livewire::actingAs($user)->test(MasterItemIndex::class, ['masterType' => $vendorType]);
        $component->set('selectedIds', [$used->id, $free->id]);
        $component->call('deleteSelected');

        $this->assertDatabaseHas('master_items', ['id' => $used->id]);
        $this->assertDatabaseMissing('master_items', ['id' => $free->id]);
    }
}
