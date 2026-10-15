<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CashAccountExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    public function query(): Builder
    {
        return CashAccount::query()->orderBy('kode');
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Type', 'Opening Balance', 'Default', 'Status', 'Description'];
    }

    public function map($account): array
    {
        return [
            $this->spreadsheetValue($account->kode),
            $this->spreadsheetValue($account->nama),
            $account->jenis->value,
            (float) $account->saldo_awal,
            $account->is_default ? 'yes' : 'no',
            $account->status->value,
            $this->spreadsheetValue($account->keterangan),
        ];
    }
}
