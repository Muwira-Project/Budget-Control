<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Cash Flow per Account (Kas Besar) report.
 * Summarises opening balance, cash in/out, transfers, and closing balance per account.
 */
class CashFlowReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    private array $reportData;

    public function __construct(
        private readonly ?string $startDate,
        private readonly ?string $endDate,
    ) {
        $this->reportData = app(ReportService::class)->cashFlow($startDate, $endDate);
    }

    /**
     * Sheet title.
     */
    public function title(): string
    {
        return 'Cash Flow per Account';
    }

    /**
     * Column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        $period = '';
        if ($this->startDate || $this->endDate) {
            $period = ' | '.($this->startDate ?? '-').' s/d '.($this->endDate ?? '-');
        }

        return [
            'Account Code',
            'Account Name',
            'Type'.$period,
            'Opening Balance',
            'Cash In',
            'Cash Out',
            'Transfer In',
            'Transfer Out',
            'Closing Balance',
        ];
    }

    /**
     * Build the data rows including a total row at the bottom.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->reportData['rows'] as $row) {
            $rows[] = [
                $row['kode'],
                $row['nama'],
                $row['jenis'],
                (float) $row['saldo_awal'],
                (float) $row['masuk'],
                (float) $row['keluar'],
                (float) $row['tr_in'],
                (float) $row['tr_out'],
                (float) $row['saldo_akhir'],
            ];
        }

        // Empty separator row
        $rows[] = ['', '', '', '', '', '', '', '', ''];

        // Total row
        $totals = $this->reportData['totals'];
        $rows[] = [
            '',
            'TOTAL',
            '',
            (float) $totals['saldo_awal'],
            (float) $totals['masuk'],
            (float) $totals['keluar'],
            (float) $totals['tr_in'],
            (float) $totals['tr_out'],
            (float) $totals['saldo_akhir'],
        ];

        return $rows;
    }

    /**
     * Apply styles to heading row and total row.
     */
    public function styles(Worksheet $sheet): array
    {
        $totalRow = count($this->reportData['rows']) + 3; // heading + data + separator + total

        // Heading row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Total row
        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f1f5f9']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // Number format for amount columns D–I
        $sheet->getStyle("D2:I{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }
}
