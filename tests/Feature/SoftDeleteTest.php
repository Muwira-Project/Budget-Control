<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use App\Services\CashAccountService;
use App\Services\CashflowService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_account_soft_delete_and_restore(): void
    {
        $account = CashAccount::factory()->create(['kode' => 'KAS-001']);

        $account->delete();

        $this->assertSoftDeleted('cash_accounts', ['id' => $account->id]);
        $this->assertSame(0, CashAccount::count()); // excluded from default scope

        $account->restore();

        $this->assertDatabaseHas('cash_accounts', ['id' => $account->id, 'deleted_at' => null]);
        $this->assertSame(1, CashAccount::count());
    }

    public function test_cash_account_kode_can_be_reused_after_soft_delete(): void
    {
        $account = CashAccount::factory()->create(['kode' => 'KAS-002']);
        $account->delete();

        // Re-creating with the same kode must succeed (partial unique index).
        $replacement = CashAccount::factory()->create(['kode' => 'KAS-002']);

        $this->assertDatabaseHas('cash_accounts', ['id' => $replacement->id, 'kode' => 'KAS-002', 'deleted_at' => null]);
        $this->assertSame(1, CashAccount::count()); // only the active one
    }

    public function test_receivable_project_can_be_recreated_after_soft_delete(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $receivable = Receivable::factory()->create(['project_id' => $project->id]);

        $receivable->delete();
        $this->assertSoftDeleted('receivables', ['id' => $receivable->id]);

        // A new receivable for the same project must be allowed.
        $replacement = Receivable::factory()->create(['project_id' => $project->id]);

        $this->assertDatabaseHas('receivables', ['id' => $replacement->id, 'deleted_at' => null]);
    }

    public function test_payment_delete_soft_deletes_cashflow_and_reverses(): void
    {
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        app(PaymentService::class)->delete($payment);

        // Payment and its cashflow are soft-deleted, not gone.
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertSoftDeleted('cashflows', ['payment_id' => $payment->id]);

        // Ledger/balance queries exclude soft-deleted rows.
        $this->assertSame(0, Cashflow::where('payment_id', $payment->id)->count());
        $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_soft_deleted_rows_excluded_from_cashflow_statistics(): void
    {
        $user = User::factory()->create();
        $account = CashAccount::factory()->create();
        $entry = Cashflow::factory()->create([
            'tanggal' => '2026-07-20',
            'status' => 'posted',
            'jenis' => 'masuk',
            'nominal' => 50000000,
            'cash_account_id' => $account->id,
        ]);

        $stats = app(CashflowService::class)->statistics('2026-07-01', '2026-08-31');
        $this->assertSame(50000000.0, $stats['total_masuk']);

        $entry->delete();

        $stats = app(CashflowService::class)->statistics('2026-07-01', '2026-08-31');
        $this->assertSame(0.0, $stats['total_masuk']);
    }

    public function test_fund_transfer_can_be_soft_deleted(): void
    {
        $from = CashAccount::factory()->create();
        $to = CashAccount::factory()->create();
        $transfer = FundTransfer::factory()->create([
            'dari_cash_account_id' => $from->id,
            'ke_cash_account_id' => $to->id,
            'status' => 'draft',
        ]);

        $transfer->delete();

        $this->assertSoftDeleted('fund_transfers', ['id' => $transfer->id]);
        $this->assertSame(0, FundTransfer::count());
    }

    public function test_payable_can_be_soft_deleted(): void
    {
        $payable = Payable::factory()->create();

        $payable->delete();

        $this->assertSoftDeleted('payables', ['id' => $payable->id]);
        $this->assertSame(0, Payable::count());
    }

    public function test_cash_account_with_transactions_cannot_be_deleted(): void
    {
        $account = CashAccount::factory()->create(['kode' => 'KAS-HIST']);
        Cashflow::factory()->create([
            'cash_account_id' => $account->id,
            'status' => 'posted',
            'jenis' => 'masuk',
            'nominal' => 100000,
        ]);

        try {
            app(CashAccountService::class)->delete($account);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            $this->assertNotSoftDeleted('cash_accounts', ['id' => $account->id]);
        }
    }

    public function test_cash_account_without_transactions_can_be_deleted(): void
    {
        $account = CashAccount::factory()->create(['kode' => 'KAS-EMPTY']);

        app(CashAccountService::class)->delete($account);

        $this->assertSoftDeleted('cash_accounts', ['id' => $account->id]);
    }
}
