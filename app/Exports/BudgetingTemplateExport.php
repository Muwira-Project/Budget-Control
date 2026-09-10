<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetingTemplateExport implements FromArray, WithStyles
{
    /**
     * Modernized template header and sample rows for Budgeting imports (Project & Non-Project).
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            [
                'Project Code',
                'Project Name',
                'Budgeting Number',
                'Account Code',
                'Account Name',
                'Type',
                'Party Type',
                'Party Name',
                'Budget',
                'Allocation',
                'Status',
            ],
            // Contoh 1: Alokasi Budget Proyek
            [
                'PRJ-001',
                'Proyek Pembangunan Gedung A',
                'BP-2025-001',
                '5-100',
                'Beban Pokok Proyek',
                'other_outcome',
                '',
                '',
                50000000,
                50000000,
                'approved',
            ],
            // Contoh 2: Non-Project Hutang Vendor/Supplier (AP)
            [
                'Non-Project',
                '',
                '',
                '2-100',
                'Hutang Usaha',
                'ap',
                'supplier',
                'PT Semen Indonesia',
                25000000,
                25000000,
                'approved',
            ],
            // Contoh 3: Non-Project Piutang Operasional (AR)
            [
                'Non-Project',
                '',
                '',
                '1-200',
                'Piutang Usaha',
                'ar',
                'customer',
                'PT Berkah Sentosa',
                15000000,
                15000000,
                'approved',
            ],
            // Contoh 4: Non-Project Beban Operasional / Pengeluaran Rutin
            [
                'Non-Project',
                '',
                '',
                '6-100',
                'Beban Operasional Kantor',
                'other_outcome',
                '',
                'Biaya Listrik, Internet & Air',
                4500000,
                4500000,
                'approved',
            ],
            // Contoh 5: Non-Project Pendapatan Lain-lain
            [
                'Non-Project',
                '',
                '',
                '4-200',
                'Pendapatan Bunga & Investasi',
                'other_income',
                '',
                'Bunga Deposito Bank Mandiri',
                1250000,
                1250000,
                'approved',
            ],
        ];
    }

    /**
     * Style header row.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1F2937']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
