<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Dynamic Excel template for Receivable (AR) import.
 * Supports both "with project code" and "without project code" modes.
 */
class ReceivableTemplateExport implements FromArray
{
    public function __construct(protected bool $useProjectCode = true)
    {
    }

    /**
     * Template header + example rows + notes for AR import.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        if ($this->useProjectCode) {
            return [
                ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
                ['PRJ-001', 'vendor', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Tagihan pertama'],
                ['PRJ-002', 'investor', 'Investor A', '2026-08-20', 'INV-2026-002', '2026-09-19', 75000000, 25000000, 'Tagihan sebagain'],
                [],
                ['NOTE: Party Type Code: vendor, supplier, mandor, investor. Party Name must exist in Master Data.'],
                ['Date format: YYYY-MM-DD. Invoice No. must be unique per project. Amount and Paid are numeric.'],
                ['Party must have AR flag enabled in Master Data.'],
            ];
        }

        // Without project code - globally unique invoice
        return [
            ['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['vendor', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Tagihan pertama'],
            ['investor', 'Investor A', '2026-08-20', 'INV-2026-002', '2026-09-19', 75000000, 25000000, 'Tagihan sebagain'],
            [],
            ['NOTE: Party Type Code: vendor, supplier, mandor, investor. Party Name must exist in Master Data.'],
            ['Date format: YYYY-MM-DD. Invoice No. must be globally unique. Amount and Paid are numeric.'],
            ['Party must have AR flag enabled in Master Data.'],
        ];
    }
}