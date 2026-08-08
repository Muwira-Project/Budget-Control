<?php

namespace Tests\Feature;

use App\Livewire\Suppliers\Create as CreateSupplier;
use App\Livewire\Suppliers\Edit as EditSupplier;
use App\Livewire\Suppliers\Index as IndexSupplier;
use App\Models\Akun;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Supplier::factory()->create();

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    public function test_supplier_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateSupplier::class)
            ->set('kode', 'SPL-001')
            ->set('nama', 'PT Sumber Material')
            ->set('telepon', '024-5554101')
            ->set('alamat', 'Semarang')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['kode' => 'SPL-001', 'nama' => 'PT Sumber Material']);
    }

    public function test_supplier_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->create(['kode' => 'SPL-001']);

        Livewire::actingAs($user)
            ->test(CreateSupplier::class)
            ->set('kode', 'SPL-001')
            ->set('nama', 'Supplier Lain')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_supplier_can_be_updated(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        Livewire::actingAs($user)
            ->test(EditSupplier::class, ['supplier' => $supplier])
            ->set('nama', 'PT Supplier Baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'nama' => 'PT Supplier Baru']);
    }

    public function test_supplier_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexSupplier::class)
            ->call('delete', $supplier->id);

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_used_by_realisasi_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        Realisasi::factory()->forSupplier()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'supplier_id' => $supplier->id]);

        Livewire::actingAs($user)
            ->test(IndexSupplier::class)
            ->call('delete', $supplier->id);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_can_be_searched(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->create(['kode' => 'SPL-001', 'nama' => 'PT Sumber Material']);
        Supplier::factory()->create(['kode' => 'SPL-002', 'nama' => 'CV Bahan Bangunan']);

        Livewire::actingAs($user)
            ->test(IndexSupplier::class)
            ->set('search', 'Sumber')
            ->assertSee('PT Sumber Material')
            ->assertDontSee('CV Bahan Bangunan');
    }
}
