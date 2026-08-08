<?php

namespace Tests\Feature;

use App\Livewire\Vendors\Create as CreateVendor;
use App\Livewire\Vendors\Edit as EditVendor;
use App\Livewire\Vendors\Index as IndexVendor;
use App\Models\Akun;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('vendors.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Vendor::factory()->create();

        $this->actingAs($user)
            ->get(route('vendors.index'))
            ->assertOk();
    }

    public function test_vendor_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateVendor::class)
            ->set('kode', 'VND-001')
            ->set('nama', 'PT Maju Jaya')
            ->set('telepon', '021-5551001')
            ->set('alamat', 'Jakarta')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', ['kode' => 'VND-001', 'nama' => 'PT Maju Jaya']);
    }

    public function test_vendor_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Vendor::factory()->create(['kode' => 'VND-001']);

        Livewire::actingAs($user)
            ->test(CreateVendor::class)
            ->set('kode', 'VND-001')
            ->set('nama', 'Vendor Lain')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_vendor_can_be_updated(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(EditVendor::class, ['vendor' => $vendor])
            ->set('nama', 'PT Vendor Baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'nama' => 'PT Vendor Baru']);
    }

    public function test_vendor_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexVendor::class)
            ->call('delete', $vendor->id);

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_vendor_used_by_realisasi_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'vendor_id' => $vendor->id, 'supplier_id' => null]);

        Livewire::actingAs($user)
            ->test(IndexVendor::class)
            ->call('delete', $vendor->id);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
    }

    public function test_vendor_can_be_searched(): void
    {
        $user = User::factory()->create();
        Vendor::factory()->create(['kode' => 'VND-001', 'nama' => 'PT Maju Jaya']);
        Vendor::factory()->create(['kode' => 'VND-002', 'nama' => 'CV Karya Bangun']);

        Livewire::actingAs($user)
            ->test(IndexVendor::class)
            ->set('search', 'Maju')
            ->assertSee('PT Maju Jaya')
            ->assertDontSee('CV Karya Bangun');
    }
}
