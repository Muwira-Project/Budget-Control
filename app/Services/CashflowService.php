<?php

namespace App\Services;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\NonProjectExpense;
use App\Models\PaymentRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class CashflowService
{
    /**
     * Create a manual (draft) or automatic (posted) cashflow entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Cashflow
    {
        return Cashflow::create([
            'tanggal' => $data['tanggal'],
            'jenis' => $data['jenis'],
            'sumber' => $data['sumber'],
            'payment_request_id' => $data['payment_request_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'non_project_expense_id' => $data['non_project_expense_id'] ?? null,
            'cash_account_id' => $data['cash_account_id'] ?? CashAccount::defaultId(),
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
            'status' => $data['status'] ?? KasStatus::Posted,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Delete a non-posted manual cashflow entry.
     */
    public function delete(Cashflow $cashflow): void
    {
        if ($cashflow->isPosted()) {
            throw new \LogicException('Posted cash records cannot be deleted. Use the settlement void workflow if needed.');
        }

        $cashflow->delete();
    }

    /**
     * Submit a draft cashflow entry for admin approval.
     */
    public function submit(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Draft) {
            throw new \LogicException('Only draft cash records can be submitted.');
        }

        $cashflow->update([
            'status' => KasStatus::Waiting,
            'submitted_by' => auth()->id(),
        ]);

        app(NotificationService::class)->notifyAdmins(
            'New Approval Request',
            'Manual cash entry ('.format_idr($cashflow->nominal).') is waiting for approval ('.$cashflow->sumber->label().').',
            route('approvals.index'),
        );

        return $cashflow->refresh();
    }

    /**
     * Approve a waiting cashflow entry (admin).
     */
    public function approve(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Waiting) {
            throw new \LogicException('Only pending cash records can be approved.');
        }

        $cashflow->update([
            'status' => KasStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $cashflow->refresh();
    }

    /**
     * Post an approved cashflow entry (admin). Affects ledgers and issues the voucher.
     */
    public function post(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Approved) {
            throw new \LogicException('Only approved cash records can be posted.');
        }

        $cashflow->update([
            'status' => KasStatus::Posted,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        app(VoucherService::class)->generateFor($cashflow);

        return $cashflow->refresh();
    }

    /**
     * Reject a pending/approved cashflow entry (admin).
     */
    public function reject(Cashflow $cashflow, string $reason): Cashflow
    {
        if ($cashflow->status !== KasStatus::Waiting && $cashflow->status !== KasStatus::Approved) {
            throw new \LogicException('Only pending or approved cash records can be rejected.');
        }

        $cashflow->update([
            'status' => KasStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $cashflow->refresh();
    }

    /**
     * Register the posted cash out entry for a paid payment request.
     */
    public function registerPaymentRequestPaid(PaymentRequest $paymentRequest): Cashflow
    {
        $projectName = $paymentRequest->project()->value('nama');

        return Cashflow::firstOrCreate([
            'payment_request_id' => $paymentRequest->id,
        ], [
            'tanggal' => $paymentRequest->paid_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'jenis' => CashflowJenis::Keluar,
            'sumber' => CashflowSumber::PaymentRequest,
            'cash_account_id' => CashAccount::defaultId(),
            'status' => KasStatus::Posted,
            'nominal' => $paymentRequest->nominal,
            'keterangan' => 'Payment '.$paymentRequest->nomor.($projectName ? ' ('.$projectName.')' : ''),
        ]);
    }

    /**
     * Mirror a non-project expense into Cash Activity only when it is posted (idempotent).
     */
    public function syncNonProjectExpenseCashflow(NonProjectExpense $expense): void
    {
        $cashflow = Cashflow::where('non_project_expense_id', $expense->id)->first();

        if (! $expense->isPosted()) {
            if ($cashflow !== null) {
                $cashflow->delete();
            }

            return;
        }

        if ($cashflow !== null) {
            $cashflow->update([
                'tanggal' => $expense->tanggal->format('Y-m-d'),
                'jenis' => CashflowJenis::Keluar,
                'sumber' => CashflowSumber::NonProjectExpense,
                'cash_account_id' => $cashflow->cash_account_id ?? CashAccount::defaultId(),
                'nominal' => $expense->nominal,
                'keterangan' => 'Non-Project Expense'.($expense->keterangan ? ': '.$expense->keterangan : ' #'.$expense->id),
            ]);

            return;
        }

        $this->create([
            'tanggal' => $expense->tanggal->format('Y-m-d'),
            'jenis' => CashflowJenis::Keluar,
            'sumber' => CashflowSumber::NonProjectExpense,
            'non_project_expense_id' => $expense->id,
            'nominal' => $expense->nominal,
            'keterangan' => 'Non-Project Expense'.($expense->keterangan ? ': '.$expense->keterangan : ' #'.$expense->id),
            'status' => KasStatus::Posted,
        ]);
    }

    /**
     * List cashflow entries, optionally filtered by date range, jenis, sumber, and lokasi dana.
     */
    public function paginate(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return Cashflow::query()
            ->with(['paymentRequest', 'cashAccount', 'voucher', 'submittedBy'])
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Posted cashflow totals for the given filters (drafts are excluded).
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float, saldo_rekening: float|null}
     */
    public function statistics(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
    ): array {
        $query = Cashflow::query()
            ->where('status', KasStatus::Posted)
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate));

        $totalMasuk = (float) (clone $query)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
        $totalKeluar = (float) (clone $query)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

        $saldoRekening = null;

        if ($cashAccountId !== null && $account = CashAccount::find($cashAccountId)) {
            $saldoRekening = $account->saldo;
        }

        return [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => $totalMasuk - $totalKeluar,
            'saldo_rekening' => $saldoRekening,
        ];
    }
}
