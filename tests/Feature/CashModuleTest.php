<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CashflowService;
use App\Services\FundTransferService;
use App\Services\MasterItemService;
use App\Services\MasterTypeService;
use App\Services\PayableService;
use App\Services\PaymentService;
use App\Services\ReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_is_auto_generated_for_cashflow(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $cashflow = app(CashflowService::class)->create([
            'tanggal' => '2026-08-01',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 1000000,
        ]);

        $voucher = Voucher::where('cashflow_id', $cashflow->id)->first();

        $this->assertNotNull($voucher);
        $this->assertMatchesRegularExpression('/^VC-\d{4}-\d{4}$/', $voucher->nomor);
        $this->assertSame('masuk', $voucher->jenis);
        $this->assertSame('2026-08-01', $voucher->tanggal->format('Y-m-d'));
    }

    public function test_pending_kas_entries_do_not_affect_balances(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $account = CashAccount::factory()->create(['saldo_awal' => 0]);
        app(CashflowService::class)->create([
            'tanggal' => '2026-08-01',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 1000000,
            'cash_account_id' => $account->id,
            'status' => 'draft',
        ]);

        $this->assertSame(0.0, (float) $account->fresh()->saldo);
    }

    public function test_cash_account_balance_includes_flows_and_transfers(): void
    {
        $account = CashAccount::factory()->create(['saldo_awal' => 10000000]);

        app(CashflowService::class)->create([
            'tanggal' => '2026-08-01',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 5000000,
            'cash_account_id' => $account->id,
        ]);
        app(CashflowService::class)->create([
            'tanggal' => '2026-08-02',
            'jenis' => 'keluar',
            'sumber' => 'pengeluaran_lain',
            'nominal' => 2000000,
            'cash_account_id' => $account->id,
        ]);

        FundTransfer::create([
            'tanggal' => '2026-08-03',
            'dari_cash_account_id' => CashAccount::factory()->create()->id,
            'ke_cash_account_id' => $account->id,
            'nominal' => 3000000,
        ]);
        FundTransfer::create([
            'tanggal' => '2026-08-04',
            'dari_cash_account_id' => $account->id,
            'ke_cash_account_id' => CashAccount::factory()->create()->id,
            'nominal' => 1000000,
        ]);

        $this->assertSame(15000000.0, (float) $account->fresh()->saldo);
    }

    public function test_fund_transfer_rejects_same_account(): void
    {
        $account = CashAccount::factory()->create();

        $this->expectException(ValidationException::class);

        app(FundTransferService::class)->create([
            'tanggal' => '2026-08-01',
            'dari_cash_account_id' => $account->id,
            'ke_cash_account_id' => $account->id,
            'nominal' => 1000000,
        ]);
    }

    public function test_payment_void_requires_admin_approval_and_reverses(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $receivable = Receivable::factory()->create(['nominal' => 10000000]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-08-01',
            'nominal' => 4000000,
            'keterangan' => 'Cicilan 1',
        ]);

        $this->assertSame(4000000.0, (float) $receivable->fresh()->nominal_dibayar);

        app(PaymentService::class)->requestVoid($payment, 'Salah input nominal');
        $this->assertSame('pending_cancel', $payment->fresh()->status->value);

        app(PaymentService::class)->approveVoid($payment->fresh());

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_payment_void_can_be_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $receivable = Receivable::factory()->create(['nominal' => 5000000]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-08-01',
            'nominal' => 5000000,
        ]);

        app(PaymentService::class)->requestVoid($payment, 'Alasan');
        app(PaymentService::class)->rejectVoid($payment->fresh(), 'Tagihan masih valid');

        $payment = $payment->fresh();
        $this->assertSame('active', $payment->status->value);
        $this->assertSame('Tagihan masih valid', $payment->void_review_note);
        $this->assertSame(5000000.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_held_receivable_blocks_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $receivable = Receivable::factory()->create(['nominal' => 10000000]);

        app(ReceivableService::class)->hold($receivable, 'Menunggu SPK');

        try {
            app(PaymentService::class)->createForReceivable($receivable, [
                'tanggal' => '2026-08-01',
                'nominal' => 5000000,
            ]);
            $this->fail('Held receivable should block payments.');
        } catch (ValidationException) {
            $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);
        }

        app(ReceivableService::class)->release($receivable->fresh());

        app(PaymentService::class)->createForReceivable($receivable->fresh(), [
            'tanggal' => '2026-08-02',
            'nominal' => 5000000,
        ]);

        $this->assertSame(5000000.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_payable_correction_syncs_two_way_with_realisasi(): void
    {
        $realisasi = Realisasi::factory()->create(['nominal' => 10000000]);
        $payable = Payable::where('realisasi_id', $realisasi->id)->first();

        $this->assertNotNull($payable);

        // AP direction: koreksi payable ikut update realisasi/budget.
        app(PayableService::class)->update($payable, [
            'project_id' => $payable->project_id,
            'akun_id' => $payable->akun_id,
            'pihak_type_id' => $payable->pihak_type_id,
            'pihak_item_id' => $payable->pihak_item_id,
            'tanggal' => $payable->tanggal->format('Y-m-d'),
            'jatuh_tempo' => $payable->jatuh_tempo?->format('Y-m-d'),
            'nominal' => 12000000,
            'jenis_pajak' => null,
            'pajak_include' => true,
            'keterangan' => null,
        ]);

        $this->assertSame(12000000.0, (float) $realisasi->fresh()->nominal);

        // Budget direction: koreksi realisasi ikut update payable.
        $realisasi->update(['nominal' => 9000000]);

        $this->assertSame(9000000.0, (float) $payable->fresh()->nominal);
    }

    public function test_receivable_correction_updates_project_contract(): void
    {
        $project = Project::factory()->create([
            'status' => 'progress',
            'qty' => 2,
            'harga_satuan' => 1000000,
            'pajak' => 10,
        ]);

        $receivable = app(ReceivableService::class)->create([
            'project_id' => $project->id,
            'tanggal' => '2026-08-01',
            'nominal' => 2200000,
        ]);

        $receivable->update(['nominal' => 3300000]);

        $project = $project->fresh();

        $this->assertEqualsWithDelta(1500000.0, (float) $project->harga_satuan, 0.01);
        $this->assertEqualsWithDelta(3300000.0, (float) $project->nilai_total, 0.01);
        $this->assertEqualsWithDelta(3300000.0, (float) $receivable->fresh()->nominal, 0.01);
    }

    public function test_new_pages_render_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $account = CashAccount::factory()->create();
        $type = MasterType::factory()->create();
        $item = MasterItem::factory()->create(['master_type_id' => $type->id]);

        $pages = [
            route('cash-accounts.index'),
            route('cash-accounts.create'),
            route('cash-accounts.edit', $account),
            route('fund-transfers.index'),
            route('fund-transfers.create'),
            route('vouchers.index'),
            route('master-types.index'),
            route('master-types.create'),
            route('master-types.edit', $type),
            route('master-items.index', $type),
            route('master-items.create', $type),
            route('master-items.edit', $item),
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_manual_cash_in_direct_post_updates_ledger(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $account = CashAccount::factory()->create(['saldo_awal' => 0]);
        $cashflow = app(CashflowService::class)->create([
            'tanggal' => '2026-08-01',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 2000000,
            'cash_account_id' => $account->id,
        ]);

        $this->assertSame('posted', $cashflow->fresh()->status->value);
        $this->assertSame(2000000.0, (float) $account->fresh()->saldo);
        $this->assertNotNull($cashflow->fresh()->voucher);
    }

    public function test_fund_transfer_affects_balances_when_created(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $source = CashAccount::factory()->create(['saldo_awal' => 5000000]);
        $target = CashAccount::factory()->create(['saldo_awal' => 1000000]);

        $transfer = app(FundTransferService::class)->create([
            'tanggal' => '2026-08-01',
            'dari_cash_account_id' => $source->id,
            'ke_cash_account_id' => $target->id,
            'nominal' => 2000000,
        ]);

        $this->assertSame('posted', $transfer->fresh()->status->value);
        $this->assertSame(3000000.0, (float) $source->fresh()->saldo);
        $this->assertSame(3000000.0, (float) $target->fresh()->saldo);
        $this->assertNotNull($transfer->fresh()->voucher);
    }

    public function test_master_type_and_items_crud(): void
    {
        $type = app(MasterTypeService::class)->create([
            'kode' => 'MT-DIV',
            'nama' => 'Divisi',
            'flag_ar' => true,
            'flag_ap' => true,
        ]);

        $this->assertTrue($type->flag_ar);
        $this->assertTrue($type->flag_ap);

        app(MasterItemService::class)->create([
            'master_type_id' => $type->id,
            'kode' => 'DIV-01',
            'nama' => 'Divisi Konstruksi',
        ]);

        $this->assertSame(1, $type->items()->count());

        app(MasterTypeService::class)->delete($type);

        $this->assertDatabaseMissing('master_types', ['id' => $type->id]);
        $this->assertDatabaseMissing('master_items', ['master_type_id' => $type->id]);
    }

    public function test_statistics_includes_opening_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $acc1 = CashAccount::factory()->create(['saldo_awal' => 10000000, 'status' => 'active']);
        $acc2 = CashAccount::factory()->create(['saldo_awal' => 5000000, 'status' => 'active']);

        $service = app(CashflowService::class);

        // Overall stats should sum all active accounts
        $statsAll = $service->statistics();
        $this->assertGreaterThanOrEqual(15000000.0, (float) $statsAll['opening_balance']);

        // Per-account stats should return the specific account's opening balance
        $statsAcc1 = $service->statistics(cashAccountId: $acc1->id);
        $this->assertSame(10000000.0, (float) $statsAcc1['opening_balance']);
        $this->assertNotNull($statsAcc1['saldo_rekening']);
    }

    public function test_cashflow_statistics_and_report_service_are_strictly_consolidated(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $accA = CashAccount::factory()->create(['saldo_awal' => 20000000, 'status' => 'active']);
        $accB = CashAccount::factory()->create(['saldo_awal' => 10000000, 'status' => 'active']);

        // Transactions BEFORE period (July)
        Cashflow::factory()->create([
            'cash_account_id' => $accA->id,
            'tanggal' => '2026-07-15',
            'nominal' => 5000000,
            'jenis' => 'masuk',
            'status' => 'posted',
        ]);
        Cashflow::factory()->create([
            'cash_account_id' => $accA->id,
            'tanggal' => '2026-07-20',
            'nominal' => 2000000,
            'jenis' => 'keluar',
            'status' => 'posted',
        ]);
        FundTransfer::factory()->create([
            'dari_cash_account_id' => $accA->id,
            'ke_cash_account_id' => $accB->id,
            'tanggal' => '2026-07-25',
            'nominal' => 3000000,
            'status' => 'posted',
        ]);

        // Transactions DURING period (August)
        Cashflow::factory()->create([
            'cash_account_id' => $accA->id,
            'tanggal' => '2026-08-05',
            'nominal' => 12000000,
            'jenis' => 'masuk',
            'status' => 'posted',
        ]);
        Cashflow::factory()->create([
            'cash_account_id' => $accB->id,
            'tanggal' => '2026-08-10',
            'nominal' => 4000000,
            'jenis' => 'keluar',
            'status' => 'posted',
        ]);
        FundTransfer::factory()->create([
            'dari_cash_account_id' => $accB->id,
            'ke_cash_account_id' => $accA->id,
            'tanggal' => '2026-08-15',
            'nominal' => 1500000,
            'status' => 'posted',
        ]);

        $cashflowService = app(CashflowService::class);
        $reportService = app(\App\Services\ReportService::class);

        $statsA = $cashflowService->statistics('2026-08-01', '2026-08-31', cashAccountId: $accA->id);
        $reportData = $reportService->cashFlow('2026-08-01', '2026-08-31');

        $rowA = collect($reportData['rows'])->firstWhere('kode', $accA->kode);
        $this->assertNotNull($rowA);

        // Account A Opening balance: 20M + 5M (in) - 2M (out) - 3M (trOut) = 20M
        $this->assertEquals($rowA['saldo_awal'], $statsA['opening_balance']);
        $this->assertEquals(20000000.0, $statsA['opening_balance']);

        // Account A August In: 12M
        $this->assertEquals($rowA['masuk'], $statsA['total_masuk']);
        $this->assertEquals(12000000.0, $statsA['total_masuk']);

        // Account A August Out: 0
        $this->assertEquals($rowA['keluar'], $statsA['total_keluar']);
        $this->assertEquals(0.0, $statsA['total_keluar']);

        // Account A Ending balance: 20M + 12M (in) - 0 (out) + 1.5M (trIn) = 33.5M
        $this->assertEquals($rowA['saldo_akhir'], $statsA['saldo_rekening']);
        $this->assertEquals(33500000.0, $statsA['saldo_rekening']);

        // Check Livewire Cashflows/Index component: tab 'cash-in' and 'cash-out' MUST report identical factual stats
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Cashflows\Index::class)
            ->set('tab', 'cash-in')
            ->set('startDate', '2026-08-01')
            ->set('endDate', '2026-08-31')
            ->set('cashAccountId', $accA->id)
            ->assertSet('stats.total_masuk', 12000000.0)
            ->assertSet('stats.total_keluar', 0.0)
            ->assertSet('stats.opening_balance', 20000000.0)
            ->assertSet('stats.saldo_rekening', 33500000.0);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Cashflows\Index::class)
            ->set('tab', 'cash-out')
            ->set('startDate', '2026-08-01')
            ->set('endDate', '2026-08-31')
            ->set('cashAccountId', $accA->id)
            ->assertSet('stats.total_masuk', 12000000.0)
            ->assertSet('stats.total_keluar', 0.0)
            ->assertSet('stats.opening_balance', 20000000.0)
            ->assertSet('stats.saldo_rekening', 33500000.0);
    }
}
