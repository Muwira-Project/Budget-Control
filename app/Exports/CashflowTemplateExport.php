<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Cashflow import.
 *
 * Supports Cash In (Masuk) and Cash Out (Keluar) with proper column order.
 */
class CashflowTemplateExport implements FromArray
{
    /**
     * Header only: every non-empty row after the header is treated as data.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Amount', 'Description'],
        ];
    }
}
