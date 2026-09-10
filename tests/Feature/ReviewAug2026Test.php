<?php

namespace Tests\Feature;

use App\Enums\CashflowSumber;
use App\Livewire\Cashflows\Create as CreateCashflow;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\User;
use App\Services\FundTransferService;
use App\Services\MasterItemService;
use App\Services\MasterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewAug2026Test extends TestCase
{
    use RefreshDatabase;

    public function test_cash_activity_page_renders_with_tabs_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('cashflows.index'))
            ->assertOk()
            ->assertSee('Cash Activity')
            ->assertSee('Cash In')
            ->assertSee('Cash Out')
            ->assertSee('Fund Transfer');
    }

    public function test_ar_ap_page_renders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('ar-ap.index'))
            ->assertOk()
            ->assertSee('AR & AP')
            ->assertSee('Receivable')
            ->assertSee('Payable');
    }

    public function test_manual_cash_out_requires_expense_account(): void
    {
        $admin = User::factory()->admin()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateCashflow::class)
            ->set('jenis', 'keluar')
            ->set('tanggal', '2026-08-10')
            ->set('nominal', '1500000')
            ->set('akunId', $akun->id)
            ->set('keterangan', 'Sewa kantor')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashflows.index'));

        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'keluar',
            'sumber' => 'pengeluaran_lain',
            'akun_id' => $akun->id,
            'nominal' => 1500000,
            'status' => 'posted',
        ]);
    }

    public function test_creating_master_menu_with_custom_fields(): void
    {
        $type = app(MasterTypeService::class)->create([
            'kode' => 'PIC',
            'nama' => 'PIC',
            'fields' => [
                ['label' => 'Telepon', 'tipe' => 'text', 'is_required' => false],
                ['label' => 'Jabatan', 'tipe' => 'text', 'is_required' => true],
            ],
        ]);

        $this->assertSame(2, $type->fields()->count());
        $fields = $type->fields;
        $this->assertSame('Telepon', $fields[0]->label);

        $item = app(MasterItemService::class)->create([
            'master_type_id' => $type->id,
            'kode' => 'PIC-01',
            'nama' => 'Budi',
            'data' => [(string) $fields[0]->id => '08123456789', (string) $fields[1]->id => 'Site Manager'],
        ]);

        $this->assertDatabaseHas('master_items', ['id' => $item->id]);
        $this->assertSame('Site Manager', $item->data[(string) $fields[1]->id]);
    }

    public function test_fund_transfer_gets_voucher_after_posted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $source = CashAccount::factory()->create(['saldo_awal' => 5000000]);
        $target = CashAccount::factory()->create(['saldo_awal' => 1000000]);

        $transfer = app(FundTransferService::class)->create([
            'tanggal' => '2026-08-05',
            'dari_cash_account_id' => $source->id,
            'ke_cash_account_id' => $target->id,
            'nominal' => 2000000,
        ]);

        $this->assertNotNull($transfer->fresh()->voucher);
        $this->assertSame('transfer', $transfer->fresh()->voucher->jenis);
    }

    public function test_cashflow_sumber_enum_has_no_payment_request_case(): void
    {
        $this->assertFalse(defined(CashflowSumber::class.'::PaymentRequest'));
        $this->assertFalse(defined(CashflowSumber::class.'::NonProjectExpense'));
    }
}
