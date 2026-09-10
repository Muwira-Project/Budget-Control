<?php

namespace App\Services;

use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FundTransferService
{
    /**
     * Create a fund transfer (immediately posted).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FundTransfer
    {
        if ((int) $data['dari_cash_account_id'] === (int) $data['ke_cash_account_id']) {
            throw ValidationException::withMessages(['ke_cash_account_id' => 'Source and destination accounts must be different.']);
        }

        $transfer = FundTransfer::create([
            'tanggal'               => $data['tanggal'],
            'dari_cash_account_id'  => $data['dari_cash_account_id'],
            'ke_cash_account_id'    => $data['ke_cash_account_id'],
            'nominal'               => $data['nominal'],
            'keterangan'            => $data['keterangan'] ?? null,
            'created_by'            => auth()->id(),
            'status'                => KasStatus::Posted,
            'posted_by'             => auth()->id(),
            'posted_at'             => now(),
        ]);

        app(VoucherService::class)->generateForFundTransfer($transfer);

        return $transfer;
    }

    /**
     * Update a fund transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FundTransfer $transfer, array $data): FundTransfer
    {
        $dariId = (int) ($data['dari_cash_account_id'] ?? $transfer->dari_cash_account_id);
        $keId = (int) ($data['ke_cash_account_id'] ?? $transfer->ke_cash_account_id);

        if ($dariId === $keId) {
            throw ValidationException::withMessages(['ke_cash_account_id' => 'Source and destination accounts must be different.']);
        }

        $transfer->update([
            'tanggal'              => $data['tanggal'] ?? $transfer->tanggal,
            'dari_cash_account_id' => $dariId,
            'ke_cash_account_id'   => $keId,
            'nominal'              => $data['nominal'] ?? $transfer->nominal,
            'keterangan'           => array_key_exists('keterangan', $data) ? $data['keterangan'] : $transfer->keterangan,
        ]);

        if ($transfer->voucher) {
            $transfer->voucher->update([
                'tanggal'    => $transfer->tanggal,
                'keterangan' => $transfer->keterangan,
            ]);
        }

        \App\Services\DashboardService::clearCache();

        return $transfer->refresh();
    }

    /**
     * Post an unposted fund transfer.
     */
    public function post(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->isPosted()) {
            return $transfer;
        }

        $transfer->update([
            'status'    => KasStatus::Posted,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        app(VoucherService::class)->generateForFundTransfer($transfer);

        \App\Services\DashboardService::clearCache();

        return $transfer->refresh();
    }

    /**
     * Delete a non-posted fund transfer.
     */
    public function delete(FundTransfer $transfer): void
    {
        if ($transfer->isPosted()) {
            throw new \LogicException('Posted fund transfers cannot be deleted.');
        }

        $transfer->delete();
    }

    /**
     * List fund transfers.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FundTransfer::query()
            ->with(['dariCashAccount', 'keCashAccount', 'submittedBy'])
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * The accounts usable as transfer destinations / sources.
     */
    public function accounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }
}
