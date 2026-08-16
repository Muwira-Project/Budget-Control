<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FundTransferService
{
    /**
     * Create a fund transfer between two accounts.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FundTransfer
    {
        if ((int) $data['dari_cash_account_id'] === (int) $data['ke_cash_account_id']) {
            throw ValidationException::withMessages(['ke_cash_account_id' => 'Source and destination accounts must be different.']);
        }

        return FundTransfer::create([
            'tanggal' => $data['tanggal'],
            'dari_cash_account_id' => $data['dari_cash_account_id'],
            'ke_cash_account_id' => $data['ke_cash_account_id'],
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * List fund transfers.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FundTransfer::query()
            ->with(['dariCashAccount', 'keCashAccount'])
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
