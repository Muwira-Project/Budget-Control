<?php

namespace Tests\Feature;

use App\Livewire\Payables\Create as CreatePayable;
use App\Livewire\Payables\Index as IndexPayable;
use App\Models\Akun;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayableTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payables.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Payable::factory()->create();

        $this->actingAs($user)
            ->get(route('payables.index'))
            ->assertOk();
    }

    public function test_payable_is_auto_created_from_realisasi(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $vendor = Vendor::factory()->create();

        $realisasi = Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => null,
            'nominal' => 25000000,
        ]);

        $this->assertDatabaseHas('payables', [
            'realisasi_id' => $realisasi->id,
            'project_id' => $project->id,
            'vendor_id' => $vendor->id,
            'nominal' => 25000000,
        ]);
    }

    public function test_payable_can_be_created_manually(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePayable::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '50000000')
            ->set('jenisPajak', 'ppn')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('payables.index'));

        $this->assertDatabaseHas('payables', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'nominal' => 50000000,
            'jenis_pajak' => 'ppn',
        ]);
    }

    public function test_payable_requires_a_party(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        Livewire::actingAs($user)
            ->test(CreatePayable::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '50000000')
            ->call('save')
            ->assertHasErrors(['vendor_id']);
    }

    public function test_payable_status_tracks_payments(): void
    {
        $user = User::factory()->create();
        $payable = Payable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);

        $this->assertSame('belum_bayar', $payable->status->value);

        $payable->update(['nominal_dibayar' => 100000000]);
        $this->assertSame('lunas', $payable->status->value);
    }

    public function test_payable_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = Payable::factory()->create();

        Livewire::actingAs($admin)
            ->test(IndexPayable::class)
            ->call('delete', $payable->id);

        $this->assertSoftDeleted('payables', ['id' => $payable->id]);
    }

    public function test_payable_aging_filter_1_30(): void
    {
        $user = User::factory()->create();
        $old = Payable::factory()->create(['jatuh_tempo' => now()->subDays(120), 'nominal' => 100000, 'nominal_dibayar' => 0]);
        $recent = Payable::factory()->create(['jatuh_tempo' => now()->subDays(10), 'nominal' => 100000, 'nominal_dibayar' => 0]);

        $component = Livewire::actingAs($user)
            ->test(IndexPayable::class)
            ->set('agingFilter', '1_30');

        $ids = collect($component->instance()->payables->items())->pluck('id');
        $this->assertFalse($ids->contains($old->id));
        $this->assertTrue($ids->contains($recent->id));
    }
}
