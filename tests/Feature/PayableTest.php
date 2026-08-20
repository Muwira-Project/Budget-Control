<?php

namespace Tests\Feature;

use App\Livewire\Payables\Create as CreatePayable;
use App\Livewire\Payables\Index as IndexPayable;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Services\PayableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);

        $realisasi = Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $vendor->id,
            'nominal' => 25000000,
        ]);

        $this->assertDatabaseHas('payables', [
            'realisasi_id' => $realisasi->id,
            'project_id' => $project->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $vendor->id,
            'nominal' => 25000000,
        ]);
    }

    public function test_payable_can_be_created_manually(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);

        Livewire::actingAs($user)
            ->test(CreatePayable::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('pihakTypeId', $vendorType->id)
            ->set('pihakItemId', $vendor->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '50000000')
            ->set('jenisPajak', 'ppn')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('payables.index'));

        $this->assertDatabaseHas('payables', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $vendor->id,
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
            ->assertHasErrors(['pihak_item_id']);
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

    public function test_payable_nominal_cannot_be_reduced_below_paid_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = Payable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 80000000]);

        $this->expectException(ValidationException::class);

        app(PayableService::class)->update($payable, [
            'project_id' => $payable->project_id,
            'akun_id' => $payable->akun_id,
            'pihak_type_id' => $payable->pihak_type_id,
            'pihak_item_id' => $payable->pihak_item_id,
            'tanggal' => $payable->tanggal->format('Y-m-d'),
            'nominal' => 50000000,
            'keterangan' => null,
        ]);
    }

    public function test_payable_rejects_duplicate_invoice_number(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        Payable::factory()->create(['nomor_invoice' => 'INV-2026-001']);

        Livewire::actingAs($user)
            ->test(CreatePayable::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('pihakTypeId', $vendorType->id)
            ->set('pihakItemId', $vendor->id)
            ->set('tanggal', '2026-07-01')
            ->set('nomorInvoice', 'inv-2026-001')
            ->set('nominal', '50000000')
            ->call('save')
            ->assertHasErrors(['nomor_invoice']);
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
