<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class RealisasiTemplateExport implements FromArray
{
    /**
     * Template header for realisasi imports.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
        ];
    }
}
