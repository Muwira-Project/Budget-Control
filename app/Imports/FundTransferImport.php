<?php

namespace App\Imports;

use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Import Fund Transfer records from Excel.
 */
class FundTransferImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the fund transfer import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['date', 'from_cash_account', 'to_cash_account', 'amount', 'description'];

    /**
     * Validate a single fund transfer row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $date = trim((string) $row->get('date'));
        $fromAccountCode = trim((string) $row->get('from_cash_account'));
        $toAccountCode = trim((string) $row->get('to_cash_account'));
        $nominal = $this->normalizeNominal($row->get('amount'));
        $keterangan = trim((string) $row->get('description'));

        if ($date === '') {
            return [false, null, 'Date is required'];
        }

        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            return [false, null, 'Date must be in YYYY-MM-DD format'];
        }

        if ($fromAccountCode === '') {
            return [false, null, 'From Cash Account is required'];
        }

        if ($toAccountCode === '') {
            return [false, null, 'To Cash Account is required'];
        }

        if (strcasecmp($fromAccountCode, $toAccountCode) === 0) {
            return [false, null, 'From and To Cash Accounts must be different'];
        }

        if ($nominal === null || $nominal <= 0) {
            return [false, null, 'Amount must be a positive number'];
        }

        // Validate From Cash Account
        $fromAccount = CashAccount::where('kode', $fromAccountCode)->where('status', 'active')->first();
        if (! $fromAccount) {
            return [false, null, 'Source Cash Account "'.$fromAccountCode.'" not found or inactive'];
        }

        // Validate To Cash Account
        $toAccount = CashAccount::where('kode', $toAccountCode)->where('status', 'active')->first();
        if (! $toAccount) {
            return [false, null, 'Destination Cash Account "'.$toAccountCode.'" not found or inactive'];
        }

        // Check duplicate in file
        $key = $date.'-'.$fromAccountCode.'-'.$toAccountCode.'-'.(string) $nominal.'-'.$keterangan;
        if (isset($seenKeys[$key])) {
            return [false, null, 'Duplicate entry: same date, accounts, amount, and description already in file'];
        }
        $seenKeys[$key] = true;

        return [
            true,
            [
                'tanggal' => $date,
                'dari_cash_account_id' => $fromAccount->id,
                'ke_cash_account_id' => $toAccount->id,
                'nominal' => $nominal,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'status' => KasStatus::Draft,
                'created_by' => auth()->id(),
            ],
            null,
        ];
    }

    /**
     * Persist a validated fund transfer row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        FundTransfer::create($data);
    }
}
