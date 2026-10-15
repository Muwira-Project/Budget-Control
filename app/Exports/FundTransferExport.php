<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\FundTransfer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FundTransferExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    public function query(): Builder
    {
        return FundTransfer::query()
            ->with(['dariCashAccount', 'keCashAccount', 'createdBy'])
            ->orderBy('tanggal')->orderBy('id');
    }

    public function headings(): array
    {
        return ['Date', 'From Cash Account', 'To Cash Account', 'Amount', 'Description', 'Status', 'Created By'];
    }

    public function map($transfer): array
    {
        return [
            $transfer->tanggal->format('Y-m-d'),
            $this->spreadsheetValue($transfer->dariCashAccount?->kode),
            $this->spreadsheetValue($transfer->keCashAccount?->kode),
            (float) $transfer->nominal,
            $this->spreadsheetValue($transfer->keterangan),
            $this->spreadsheetValue($transfer->status?->label() ?? ''),
            $this->spreadsheetValue($transfer->createdBy?->name),
        ];
    }
}
