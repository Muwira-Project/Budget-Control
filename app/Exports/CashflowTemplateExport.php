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
    /** Header + example rows */
    public function array(): array
    {
        return [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Receivable Invoice No', 'Payable Invoice No', 'Amount', 'Description'],
            ['2026-01-15', 'Masuk', 'Pendapatan', 'Kas Utama', '4100', 'PRJ-001', 'Customer', 'PT ABC', 'INV-2026-001', '', '1000000', 'Terima piutang'],
            ['2026-01-20', 'Keluar', 'Pengeluaran Lain', 'Bank BCA', '6100', '', 'Vendor', 'CV XYZ', '', 'INV-2026-002', '500000', 'Bayar hutang'],
        ];
    }
}
