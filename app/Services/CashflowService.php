<?php

namespace App\Services;

use App\Enums\CashflowJenis;
use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Kategori;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashflowService
{
    /**
     * Create a manual (draft) or automatic (posted) cashflow entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Cashflow
    {
        return DB::transaction(function () use ($data): Cashflow {
            $status = $data['status'] ?? KasStatus::Posted;
            if (is_string($status)) {
                $status = KasStatus::from($status);
            }

            $cashflow = Cashflow::create([
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'],
                'sumber' => $data['sumber'],
                'payment_id' => $data['payment_id'] ?? null,
                'cash_account_id' => $data['cash_account_id'] ?? CashAccount::defaultId(),
                'akun_id' => $data['akun_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'pihak_type_id' => $data['pihak_type_id'] ?? null,
                'pihak_item_id' => $data['pihak_item_id'] ?? null,
                'nominal' => $data['nominal'],
                'keterangan' => $data['keterangan'] ?? null,
                'status' => $status,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'posted_by' => $data['posted_by'] ?? ($status === KasStatus::Posted ? auth()->id() : null),
                'posted_at' => $data['posted_at'] ?? ($status === KasStatus::Posted ? now() : null),
            ]);

            if ($cashflow->isPosted() && $cashflow->isManual() && $cashflow->jenis === CashflowJenis::Keluar && ($cashflow->project_id || $cashflow->pihak_item_id)) {
                $this->syncRealisasiFromCashflow($cashflow);
            }

            return $cashflow;
        });
    }

    /**
     * Update a cashflow entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Cashflow $cashflow, array $data): Cashflow
    {
        return DB::transaction(function () use ($cashflow, $data): Cashflow {
            $cashflow = Cashflow::query()->lockForUpdate()->findOrFail($cashflow->id);
            $cashflow->update([
                'tanggal' => $data['tanggal'] ?? $cashflow->tanggal,
                'nominal' => $data['nominal'] ?? $cashflow->nominal,
                'keterangan' => array_key_exists('keterangan', $data) ? $data['keterangan'] : $cashflow->keterangan,
                'cash_account_id' => $data['cash_account_id'] ?? $cashflow->cash_account_id,
                'akun_id' => array_key_exists('akun_id', $data) ? $data['akun_id'] : $cashflow->akun_id,
                'project_id' => array_key_exists('project_id', $data) ? $data['project_id'] : $cashflow->project_id,
                'pihak_type_id' => array_key_exists('pihak_type_id', $data) ? $data['pihak_type_id'] : $cashflow->pihak_type_id,
                'pihak_item_id' => array_key_exists('pihak_item_id', $data) ? $data['pihak_item_id'] : $cashflow->pihak_item_id,
            ]);

            if ($cashflow->voucher) {
                $cashflow->voucher->update([
                    'tanggal' => $cashflow->tanggal,
                    'keterangan' => $cashflow->keterangan,
                ]);
            }

            if ($cashflow->isPosted() && $cashflow->isManual()) {
                if ($cashflow->jenis === CashflowJenis::Keluar && ($cashflow->project_id || $cashflow->pihak_item_id)) {
                    $this->syncRealisasiFromCashflow($cashflow);
                } else {
                    $this->removeRealisasiFromCashflow($cashflow);
                }
            }

            DashboardService::clearCache();

            return $cashflow->refresh();
        });
    }

    /**
     * Delete a manual cashflow entry.
     */
    public function delete(Cashflow $cashflow): void
    {
        if (! $cashflow->isManual()) {
            throw new \LogicException('Records created automatically from settlements cannot be deleted. Use the settlement void workflow if needed.');
        }

        $user = auth()->user();
        if (! $user) {
            throw new \LogicException('Authentication is required to delete cash records.');
        }

        if (! $user->isAdmin() && (int) $cashflow->created_by !== (int) $user->id) {
            throw new \LogicException('You can only delete cash records you created.');
        }

        DB::transaction(function () use ($cashflow): void {
            $realisasi = Realisasi::withTrashed()
                ->where('sumber', Realisasi::SUMBER_MANUAL)
                ->where('sumber_id', $cashflow->id)
                ->first();

            $payable = $realisasi === null
                ? null
                : Payable::withTrashed()->where('realisasi_id', $realisasi->id)->first();

            if ($payable !== null && (
                Payment::query()->where('payable_id', $payable->id)->exists()
                || (float) $payable->nominal_dibayar > 0
            )) {
                throw new \LogicException('This cash record has an AP with active payments. Reverse those settlements before deleting the source record.');
            }

            if ($payable !== null && ! $payable->trashed()) {
                $payable->delete();
            }

            if ($realisasi !== null && ! $realisasi->trashed()) {
                $realisasi->delete();
            }

            // Keep the voucher while the cashflow is in Trash so restoration
            // brings back the original voucher number and record.
            $cashflow->delete();
        });
    }

    /**
     * Post a cashflow entry (issues voucher and syncs Realisasi).
     */
    public function post(Cashflow $cashflow): Cashflow
    {
        return DB::transaction(function () use ($cashflow): Cashflow {
            $cashflow = Cashflow::query()->lockForUpdate()->findOrFail($cashflow->id);
            if ($cashflow->isPosted()) {
                return $cashflow;
            }

            $cashflow->update([
                'status' => KasStatus::Posted,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            app(VoucherService::class)->generateFor($cashflow);

            if ($cashflow->isManual() && $cashflow->jenis === CashflowJenis::Keluar && ($cashflow->project_id || $cashflow->pihak_item_id)) {
                $this->syncRealisasiFromCashflow($cashflow);
            }

            return $cashflow->refresh();
        });
    }

    /**
     * Create or update Realisasi from a posted manual cashflow entry.
     */
    protected function syncRealisasiFromCashflow(Cashflow $cashflow): ?Realisasi
    {
        // Determine kategori: find by akun's kategori or default
        $kategoriId = $cashflow->akun?->kategori_id ?? Kategori::first()?->id;

        // For cash in (pendapatan), use a default income category if available
        if ($cashflow->jenis === CashflowJenis::Masuk) {
            $kategoriId = Kategori::where('nama', 'Pendapatan Lain')->first()?->id ?? $kategoriId;
        }

        $data = [
            'project_id' => $cashflow->project_id,
            'akun_id' => $cashflow->akun_id,
            'pihak_type_id' => $cashflow->pihak_type_id,
            'pihak_item_id' => $cashflow->pihak_item_id,
            'kategori_id' => $kategoriId,
            'tanggal' => $cashflow->tanggal,
            'nominal' => $cashflow->nominal,
            'keterangan' => $cashflow->keterangan ?? 'Dari cashflow #'.$cashflow->id,
            'sumber' => Realisasi::SUMBER_MANUAL,
            'sumber_id' => $cashflow->id,
        ];

        $realisasi = Realisasi::withTrashed()->firstOrNew(
            ['sumber' => Realisasi::SUMBER_MANUAL, 'sumber_id' => $cashflow->id],
            $data,
        );

        $wasTrashed = $realisasi->trashed();
        if ($wasTrashed) {
            $realisasi->restore();
        }

        $realisasi->fill($data);
        $realisasi->save();

        if ($wasTrashed) {
            app(PayableService::class)->syncFromRealisasi($realisasi->refresh());
        }

        return $realisasi->refresh();
    }

    /** Remove generated actual/AP rows when an edited cashflow no longer qualifies. */
    private function removeRealisasiFromCashflow(Cashflow $cashflow): void
    {
        $realisasi = Realisasi::withTrashed()
            ->where('sumber', Realisasi::SUMBER_MANUAL)
            ->where('sumber_id', $cashflow->id)
            ->first();

        if ($realisasi === null) {
            return;
        }

        $payable = Payable::withTrashed()->where('realisasi_id', $realisasi->id)->first();
        if ($payable !== null && (
            Payment::query()->where('payable_id', $payable->id)->exists()
            || (float) $payable->nominal_dibayar > 0
        )) {
            throw ValidationException::withMessages([
                'pihak_item_id' => 'Remove or reverse the linked AP settlements before removing the project and party from this cash record.',
            ]);
        }

        if ($payable !== null && ! $payable->trashed()) {
            $payable->delete();
        }

        if (! $realisasi->trashed()) {
            $realisasi->delete();
        }
    }

    /**
     * Reject a pending cashflow entry (kept for settlement void workflow compatibility).
     */
    public function reject(Cashflow $cashflow, string $reason): Cashflow
    {
        $cashflow->update([
            'status' => KasStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $cashflow->refresh();
    }

    /**
     * List cashflow entries, optionally filtered by date range, jenis, sumber, lokasi dana, and budget number.
     */
    public function paginate(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
        ?string $budgetNumber = null,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return Cashflow::query()
            ->with(['cashAccount', 'voucher', 'submittedBy', 'akun', 'payment', 'project.budgetPlans' => fn ($q) => $q->select('id', 'project_id', 'nomor')->orderBy('periode')])
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->when($budgetNumber, fn ($query) => $query->whereHas('project.budgetPlans', fn ($q) => $q->where('nomor', 'like', '%'.$budgetNumber.'%')))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Posted cashflow totals for the given filters (drafts are excluded).
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float, opening_balance: float, saldo_rekening: float|null}
     */
    public function statistics(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
    ): array {
        $baseQuery = Cashflow::query()
            ->where('status', KasStatus::Posted)
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate));

        $totalMasuk = ($jenis === null || $jenis === 'masuk' || $jenis === CashflowJenis::Masuk)
            ? (float) (clone $baseQuery)->where('jenis', CashflowJenis::Masuk)->sum('nominal')
            : 0.0;

        $totalKeluar = ($jenis === null || $jenis === 'keluar' || $jenis === CashflowJenis::Keluar)
            ? (float) (clone $baseQuery)->where('jenis', CashflowJenis::Keluar)->sum('nominal')
            : 0.0;

        [$openingBalance, $saldoRekening] = $this->calculateAccountBalances($cashAccountId, $startDate, $endDate);

        return [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => $totalMasuk - $totalKeluar,
            'opening_balance' => $openingBalance,
            'saldo_rekening' => $saldoRekening,
        ];
    }

    /**
     * Compute opening and closing ledger balance for an account or all active accounts across a date range.
     *
     * @return array{0: float, 1: float} [opening_balance, saldo_rekening]
     */
    private function calculateAccountBalances(?int $cashAccountId, ?string $startDate, ?string $endDate): array
    {
        if ($cashAccountId !== null && $account = CashAccount::find($cashAccountId)) {
            $inBefore = 0.0;
            $outBefore = 0.0;
            $trInBefore = 0.0;
            $trOutBefore = 0.0;

            if ($startDate !== null) {
                $cfBefore = Cashflow::query()
                    ->where('status', KasStatus::Posted)
                    ->where('cash_account_id', $cashAccountId)
                    ->whereDate('tanggal', '<', $startDate);
                $inBefore = (float) (clone $cfBefore)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
                $outBefore = (float) (clone $cfBefore)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

                $trInBefore = (float) FundTransfer::query()
                    ->where('status', 'posted')
                    ->where('ke_cash_account_id', $cashAccountId)
                    ->whereDate('tanggal', '<', $startDate)
                    ->sum('nominal');
                $trOutBefore = (float) FundTransfer::query()
                    ->where('status', 'posted')
                    ->where('dari_cash_account_id', $cashAccountId)
                    ->whereDate('tanggal', '<', $startDate)
                    ->sum('nominal');
            }

            $openingBalance = (float) $account->saldo_awal + $inBefore - $outBefore + $trInBefore - $trOutBefore;

            if ($endDate !== null) {
                $cfPeriod = Cashflow::query()
                    ->where('status', KasStatus::Posted)
                    ->where('cash_account_id', $cashAccountId);
                if ($startDate !== null) {
                    $cfPeriod->whereDate('tanggal', '>=', $startDate);
                }
                $cfPeriod->whereDate('tanggal', '<=', $endDate);

                $inPeriod = (float) (clone $cfPeriod)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
                $outPeriod = (float) (clone $cfPeriod)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

                $trInPeriodQuery = FundTransfer::query()
                    ->where('status', 'posted')
                    ->where('ke_cash_account_id', $cashAccountId)
                    ->whereDate('tanggal', '<=', $endDate);
                if ($startDate !== null) {
                    $trInPeriodQuery->whereDate('tanggal', '>=', $startDate);
                }
                $trInPeriod = (float) $trInPeriodQuery->sum('nominal');

                $trOutPeriodQuery = FundTransfer::query()
                    ->where('status', 'posted')
                    ->where('dari_cash_account_id', $cashAccountId)
                    ->whereDate('tanggal', '<=', $endDate);
                if ($startDate !== null) {
                    $trOutPeriodQuery->whereDate('tanggal', '>=', $startDate);
                }
                $trOutPeriod = (float) $trOutPeriodQuery->sum('nominal');

                $saldoRekening = $openingBalance + $inPeriod - $outPeriod + $trInPeriod - $trOutPeriod;
            } else {
                $saldoRekening = (float) $account->saldo;
            }

            return [$openingBalance, $saldoRekening];
        }

        $activeAccounts = CashAccount::where('status', 'active')->get();
        $activeIds = $activeAccounts->pluck('id')->all();

        if (empty($activeIds)) {
            return [0.0, 0.0];
        }

        $baseSaldoAwal = (float) $activeAccounts->sum('saldo_awal');

        if ($startDate !== null) {
            $cfBefore = Cashflow::query()
                ->where('status', KasStatus::Posted)
                ->whereIn('cash_account_id', $activeIds)
                ->whereDate('tanggal', '<', $startDate);
            $inBefore = (float) (clone $cfBefore)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
            $outBefore = (float) (clone $cfBefore)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

            $trInBefore = (float) FundTransfer::query()
                ->where('status', 'posted')
                ->whereIn('ke_cash_account_id', $activeIds)
                ->whereDate('tanggal', '<', $startDate)
                ->sum('nominal');
            $trOutBefore = (float) FundTransfer::query()
                ->where('status', 'posted')
                ->whereIn('dari_cash_account_id', $activeIds)
                ->whereDate('tanggal', '<', $startDate)
                ->sum('nominal');

            $openingBalance = $baseSaldoAwal + $inBefore - $outBefore + $trInBefore - $trOutBefore;
        } else {
            $openingBalance = $baseSaldoAwal;
        }

        if ($endDate !== null) {
            $cfPeriod = Cashflow::query()
                ->where('status', KasStatus::Posted)
                ->whereIn('cash_account_id', $activeIds);
            if ($startDate !== null) {
                $cfPeriod->whereDate('tanggal', '>=', $startDate);
            }
            $cfPeriod->whereDate('tanggal', '<=', $endDate);

            $inPeriod = (float) (clone $cfPeriod)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
            $outPeriod = (float) (clone $cfPeriod)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

            $trInPeriod = (float) FundTransfer::query()
                ->where('status', 'posted')
                ->whereIn('ke_cash_account_id', $activeIds)
                ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                ->whereDate('tanggal', '<=', $endDate)
                ->sum('nominal');

            $trOutPeriod = (float) FundTransfer::query()
                ->where('status', 'posted')
                ->whereIn('dari_cash_account_id', $activeIds)
                ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                ->whereDate('tanggal', '<=', $endDate)
                ->sum('nominal');

            $saldoRekening = $openingBalance + $inPeriod - $outPeriod + $trInPeriod - $trOutPeriod;
        } else {
            $balances = CashAccount::balances($activeIds);
            $saldoRekening = (float) array_sum($balances);
        }

        return [$openingBalance, $saldoRekening];
    }
}
