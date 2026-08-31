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
     * Template header + contoh baris + catatan untuk import cashflow.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Amount', 'Description'],
            ['2026-08-15', 'masuk', 'pendapatan', 'BANK-001', '4-101', 'PRJ-2025-001', 'VENDOR', 'VND-001', 50000000, 'Pembayaran termin 1'],
            ['2026-08-20', 'keluar', 'pengeluaran_lain', 'BANK-001', '5-101', '', 'SUPPLIER', 'SPL-001', 25000000, 'Pembelian material'],
            [],
            ['NOTE: Template for Cash In/Out import.'],
            ['Type: "masuk" (Cash In) or "keluar" (Cash Out)'],
            ['Source: "pendapatan" (Income), "pelunasan_ar" (AR Settlement), "pelunasan_ap" (AP Settlement), "pengeluaran_lain" (Other Expense)'],
            ['Cash Account: use kode from Master Cash Account'],
            ['Account (COA): use kode_akun from Master Akun'],
            ['Project: use project kode (optional, leave blank for non-project)'],
            ['Party Type: "VENDOR", "SUPPLIER", "MANDOR", "INVESTOR" (optional)'],
            ['Party: use MasterItem kode from corresponding Party Type (optional)'],
            ['Amount: numeric, 2 decimal places'],
            ['Date format: YYYY-MM-DD'],
        ];
    }
}
