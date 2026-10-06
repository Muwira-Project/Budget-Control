<?php

namespace App\Imports;

use App\Enums\CashAccountJenis;
use App\Enums\CashAccountStatus;
use App\Models\CashAccount;
use Illuminate\Support\Collection;

class CashAccountImport extends BaseImport
{
    protected array $expectedHeaders = ['code', 'name', 'type', 'opening_balance', 'default', 'status', 'description'];

    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $code = trim((string) $row->get('code'));
        $name = trim((string) $row->get('name'));
        $type = strtolower(trim((string) $row->get('type')));
        $opening = $this->normalizeNominal($row->get('opening_balance'));
        $defaultText = strtolower(trim((string) $row->get('default')));
        $status = strtolower(trim((string) $row->get('status')));
        $description = trim((string) $row->get('description'));

        if ($code === '' || $name === '') {
            return [false, null, 'Code and Name are required'];
        }
        if (isset($seenKeys[$code])) {
            return [false, null, 'Duplicate Cash Account Code "'.$code.'" in this file'];
        }
        if (CashAccount::withTrashed()->where('kode', $code)->exists()) {
            return [false, null, 'Cash Account Code "'.$code.'" already exists'];
        }
        if (! in_array($type, ['kas', 'bank'], true)) {
            return [false, null, 'Type must be "kas" or "bank"'];
        }
        if ($opening === null || $opening < 0) {
            return [false, null, 'Opening Balance must be zero or a positive number'];
        }
        if (! in_array($defaultText, ['', 'yes', 'no', 'true', 'false', '1', '0'], true)) {
            return [false, null, 'Default must be yes or no'];
        }
        if (! in_array($status, ['active', 'inactive'], true)) {
            return [false, null, 'Status must be active or inactive'];
        }

        $isDefault = in_array($defaultText, ['yes', 'true', '1'], true);
        if ($isDefault && (isset($seenKeys['__default']) || CashAccount::where('is_default', true)->exists())) {
            return [false, null, 'Only one cash account can be marked as default'];
        }

        $seenKeys[$code] = true;
        if ($isDefault) {
            $seenKeys['__default'] = true;
        }

        return [true, [
            'kode' => $code,
            'nama' => $name,
            'jenis' => CashAccountJenis::from($type),
            'saldo_awal' => $opening,
            'is_default' => $isDefault,
            'status' => CashAccountStatus::from($status),
            'keterangan' => $description !== '' ? $description : null,
        ], null];
    }

    protected function persist(array $data): void
    {
        CashAccount::create($data);
    }
}
