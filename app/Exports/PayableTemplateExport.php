<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Payable (AP) import.
 */
class PayableTemplateExport implements FromArray
{
    /**
     * Template header + contoh baris + catatan untuk import AP.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-001', 'vendor', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Pembayaran pertama'],
            ['PRJ-002', 'supplier', 'Supplier B', '2026-08-20', 'INV-2026-002', '2026-09-19', 75000000, 25000000, 'Pembayaran sebagian'],
            [],
            ['NOTE: Party Type Code: vendor, supplier, mandor, investor. Party Name must exist in Master Data.'],
            ['Date format: YYYY-MM-DD. Invoice No. must be unique per project. Amount and Paid are numeric.'],
            ['Party must have AP flag enabled in Master Data.'],
        ];
    }
}