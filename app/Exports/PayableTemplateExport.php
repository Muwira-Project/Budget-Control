<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Payable (AP) import.
 */
class PayableTemplateExport implements FromArray
{
    protected bool $useProjectCode = true;

    public function __construct(bool $useProjectCode = true)
    {
        $this->useProjectCode = $useProjectCode;
    }

    /**
     * Template header + contoh baris + catatan untuk import AP.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        if ($this->useProjectCode) {
            return [
                ['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
                ['PRJ-001', 'AKUN-001', 'vendor', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Pembayaran pertama'],
                ['PRJ-002', 'AKUN-002', 'supplier', 'Supplier B', '2026-08-20', 'INV-2026-002', '2026-09-19', 75000000, 25000000, 'Pembayaran sebagian'],
                [],
                ['NOTE: Party Type Code: vendor, supplier, mandor, investor. Party Name must exist in Master Data.'],
                ['Akun Code must exist in Master Data (Akun).'],
                ['Date format: YYYY-MM-DD. Invoice No. must be unique per project. Amount and Paid are numeric.'],
                ['Party must have AP flag enabled in Master Data.'],
            ];
        }

        // Without project code
        return [
            ['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['AKUN-001', 'vendor', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Pembayaran pertama'],
            ['AKUN-002', 'supplier', 'Supplier B', '2026-08-20', 'INV-2026-002', '2026-09-19', 75000000, 25000000, 'Pembayaran sebagian'],
            [],
            ['NOTE: Party Type Code: vendor, supplier, mandor, investor. Party Name must exist in Master Data.'],
            ['Akun Code must exist in Master Data (Akun).'],
            ['Date format: YYYY-MM-DD. Invoice No. must be globally unique. Amount and Paid are numeric.'],
            ['Party must have AP flag enabled in Master Data.'],
        ];
    }
}