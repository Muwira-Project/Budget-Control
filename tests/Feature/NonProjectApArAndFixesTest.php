<?php

namespace Tests\Feature;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Enums\PaymentJenis;
use App\Enums\ProjectStatus;
use App\Livewire\Payables\Create as CreatePayable;
use App\Livewire\Projects\Create as CreateProject;
use App\Livewire\Receivables\Create as CreateReceivable;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NonProjectApArAndFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_can_create_project_with_status_draft_without_error(): void
    {
        Livewire::test(CreateProject::class)
            ->set('kode', 'PRJ-TEST-DRAFT')
            ->set('nama', 'Project Draft Test')
            ->set('jenis', 'barang')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'kode' => 'PRJ-TEST-DRAFT',
            'status' => 'draft',
        ]);
    }

    public function test_cashflow_keluar_and_sumber_labels_use_outcome(): void
    {
        $this->assertSame('Outcome', CashflowJenis::Keluar->label());
        $this->assertSame('Other Outcome', CashflowSumber::PengeluaranLain->label());
        $this->assertSame('Outcome (AP Settlement)', PaymentJenis::Keluar->label());
    }

    public function test_can_create_non_project_receivable(): void
    {
        Livewire::test(CreateReceivable::class)
            ->set('projectId', '')
            ->set('tanggal', '2026-09-11')
            ->set('nominal', '15000000')
            ->set('keterangan', 'Non-project AR Test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('receivables.index'));

        $this->assertDatabaseHas('receivables', [
            'project_id' => null,
            'nominal' => 15000000,
            'keterangan' => 'Non-project AR Test',
        ]);
    }

    public function test_can_create_multiple_non_project_receivables(): void
    {
        $rec1 = Receivable::create([
            'project_id' => null,
            'tanggal' => '2026-09-11',
            'nominal' => 10000000,
            'nominal_dibayar' => 0,
        ]);

        $rec2 = Receivable::create([
            'project_id' => null,
            'tanggal' => '2026-09-12',
            'nominal' => 20000000,
            'nominal_dibayar' => 0,
        ]);

        $this->assertDatabaseHas('receivables', ['id' => $rec1->id, 'project_id' => null]);
        $this->assertDatabaseHas('receivables', ['id' => $rec2->id, 'project_id' => null]);
    }

    public function test_can_create_non_project_payable(): void
    {
        $akun = Akun::factory()->create();
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);

        Livewire::test(CreatePayable::class)
            ->set('projectId', '')
            ->set('akunId', $akun->id)
            ->set('pihakTypeId', $vendorType->id)
            ->set('pihakItemId', $vendor->id)
            ->set('tanggal', '2026-09-11')
            ->set('nominal', '5000000')
            ->set('keterangan', 'Non-project AP Test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('payables.index'));

        $this->assertDatabaseHas('payables', [
            'project_id' => null,
            'akun_id' => $akun->id,
            'nominal' => 5000000,
            'keterangan' => 'Non-project AP Test',
        ]);
    }

    public function test_can_pay_non_project_receivable_and_payable(): void
    {
        $rec = Receivable::create([
            'project_id' => null,
            'tanggal' => '2026-09-11',
            'nominal' => 10000000,
            'nominal_dibayar' => 0,
        ]);

        $paymentService = app(PaymentService::class);
        $paymentAr = $paymentService->createForReceivable($rec, [
            'tanggal' => '2026-09-11',
            'nominal' => 5000000,
            'keterangan' => 'Cicilan 1 non project',
        ]);

        $this->assertNotNull($paymentAr);
        $this->assertSame(5000000.0, (float) $rec->fresh()->nominal_dibayar);

        $akun = Akun::factory()->create();
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $vendor = MasterItem::factory()->create(['master_type_id' => $vendorType->id]);

        $pay = Payable::create([
            'project_id' => null,
            'akun_id' => $akun->id,
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => $vendor->id,
            'tanggal' => '2026-09-11',
            'nominal' => 8000000,
            'nominal_dibayar' => 0,
        ]);

        $paymentAp = $paymentService->createForPayable($pay, [
            'tanggal' => '2026-09-11',
            'nominal' => 8000000,
            'keterangan' => 'Lunas non project',
        ]);

        $this->assertNotNull($paymentAp);
        $this->assertSame(8000000.0, (float) $pay->fresh()->nominal_dibayar);
    }
}
