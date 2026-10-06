<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Excel template export for Fund Transfer import.
 */
class FundTransferTemplateExport implements FromArray
{
    /**
     * Header only: every non-empty row after the header is treated as data.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Date', 'From Cash Account', 'To Cash Account', 'Amount', 'Description'],
        ];
    }
}
