<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CashflowService;
use App\Services\FundTransferService;
use App\Services\MasterItemService;
use App\Services\MasterTypeService;
use App\Services\NonProjectExpenseService;
use App\Services\PayableService;
use App\Services\PaymentRequestService;
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

    public function test_non_project_expense_is_mirrored_to_cash_activity_after_posted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $akun = Akun::factory()->create();
        $expense = app(NonProjectExpenseService::class)->create([
            'tanggal' => '2026-08-02',
            'akun_id' => $akun->id,
            'nominal' => 2500000,
            'keterangan' => 'Listrik kantor',
        ]);

        // Draft belum masuk Cash Activity.
        $this->assertDatabaseMissing('cashflows', ['non_project_expense_id' => $expense->id]);

        app(NonProjectExpenseService::class)->submit($expense);
        app(NonProjectExpenseService::class)->approve($expense->fresh());
        app(NonProjectExpenseService::class)->post($expense->fresh());

        $cashflow = Cashflow::where('non_project_expense_id', $expense->id)->first();

        $this->assertNotNull($cashflow);
        $this->assertSame('keluar', $cashflow->jenis->value);
        $this->assertSame('non_project_expense', $cashflow->sumber->value);
        $this->assertSame(2500000.0, (float) $cashflow->nominal);

        // Expense yang sudah posted tidak bisa langsung dihapus.
        $this->expectException(\LogicException::class);
        app(NonProjectExpenseService::class)->delete($expense->fresh());
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
            'sumber' => 'non_project_expense',
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

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
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

    public function test_held_payment_request_cannot_be_marked_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $pr = PaymentRequest::factory()->create(['status' => 'approved']);

        app(PaymentRequestService::class)->hold($pr, 'Menunggu kondisi keuangan');

        try {
            app(PaymentRequestService::class)->markPaid($pr);
            $this->fail('Held payment request should not be payable.');
        } catch (\LogicException) {
            $this->assertSame('approved', $pr->fresh()->status->value);
        }

        app(PaymentRequestService::class)->release($pr->fresh());
        app(PaymentRequestService::class)->markPaid($pr->fresh());

        $this->assertSame('paid', $pr->fresh()->status->value);
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
            'vendor_id' => $payable->vendor_id,
            'supplier_id' => null,
            'mandor_id' => null,
            'investor_id' => null,
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
            route('master-items.create'),
            route('master-items.edit', $item),
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_manual_cash_in_approval_flow_posts_to_ledger(): void
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
            'status' => 'draft',
        ]);

        $this->assertSame(0.0, (float) $account->fresh()->saldo);
        $this->assertNull($cashflow->voucher);

        app(CashflowService::class)->submit($cashflow);
        $this->assertSame('waiting', $cashflow->fresh()->status->value);

        app(CashflowService::class)->approve($cashflow->fresh());
        $this->assertSame('approved', $cashflow->fresh()->status->value);
        $this->assertSame(0.0, (float) $account->fresh()->saldo);

        app(CashflowService::class)->post($cashflow->fresh());

        $this->assertSame('posted', $cashflow->fresh()->status->value);
        $this->assertSame(2000000.0, (float) $account->fresh()->saldo);
        $this->assertNotNull($cashflow->fresh()->voucher);
    }

    public function test_approval_center_page_admin_only(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)->get(route('approvals.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('approvals.index'))->assertOk();
    }

    public function test_fund_transfer_affects_balances_only_after_posted(): void
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

        $this->assertSame(5000000.0, (float) $source->fresh()->saldo);

        app(FundTransferService::class)->submit($transfer);
        app(FundTransferService::class)->approve($transfer->fresh());
        app(FundTransferService::class)->post($transfer->fresh());

        $this->assertSame(3000000.0, (float) $source->fresh()->saldo);
        $this->assertSame(3000000.0, (float) $target->fresh()->saldo);
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
}
