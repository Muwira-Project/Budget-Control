<?php

namespace App\Services;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\NonProjectExpense;
use App\Models\PaymentRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class CashflowService
{
    /**
     * Create a manual or automatic cashflow entry.
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
        ]);
    }

    /**
     * Delete a manual cashflow entry.
     */
    public function delete(Cashflow $cashflow): void
    {
        $cashflow->delete();
    }

    /**
     * Register the cash out entry for a paid payment request.
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
            'nominal' => $paymentRequest->nominal,
            'keterangan' => 'Payment '.$paymentRequest->nomor.($projectName ? ' ('.$projectName.')' : ''),
        ]);
    }

    /**
     * Mirror a non-project expense into Cash Activity (idempotent).
     */
    public function syncFromNonProjectExpense(NonProjectExpense $expense): Cashflow
    {
        $cashflow = Cashflow::firstOrNew(['non_project_expense_id' => $expense->id]);

        $cashflow->fill([
            'tanggal' => $expense->tanggal->format('Y-m-d'),
            'jenis' => CashflowJenis::Keluar,
            'sumber' => CashflowSumber::NonProjectExpense,
            'cash_account_id' => $expense->cashflow?->cash_account_id ?? CashAccount::defaultId(),
            'nominal' => $expense->nominal,
            'keterangan' => 'Non-Project Expense'.($expense->keterangan ? ': '.$expense->keterangan : ' #'.$expense->id),
        ]);

        $cashflow->save();

        return $cashflow;
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
            ->with(['paymentRequest', 'cashAccount', 'voucher'])
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
     * Cashflow totals for the given filters.
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
