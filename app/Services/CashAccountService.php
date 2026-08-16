<?php

namespace App\Services;

use App\Enums\CashAccountJenis;
use App\Enums\CashAccountStatus;
use App\Models\CashAccount;
use Illuminate\Pagination\LengthAwarePaginator;

class CashAccountService
{
    /**
     * Create a cash account (rekening fisik lokasi dana).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CashAccount
    {
        return CashAccount::create([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'jenis' => $data['jenis'] ?? CashAccountJenis::Kas->value,
            'saldo_awal' => $data['saldo_awal'] ?? 0,
            'is_default' => $data['is_default'] ?? false,
            'status' => $data['status'] ?? CashAccountStatus::Active->value,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Update a cash account.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CashAccount $account, array $data): CashAccount
    {
        $account->update($data);

        return $account->refresh();
    }

    /**
     * Delete a cash account.
     */
    public function delete(CashAccount $account): void
    {
        $account->delete();
    }

    /**
     * List cash accounts with their current balances, optionally filtered by status.
     */
    public function paginate(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $accounts = CashAccount::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('kode')
            ->paginate($perPage)
            ->withQueryString();

        $balances = CashAccount::balances($accounts->pluck('id')->all());

        foreach ($accounts as $account) {
            $account->setAttribute('balance', $balances[$account->id] ?? (float) $account->saldo_awal);
        }

        return $accounts;
    }
}
