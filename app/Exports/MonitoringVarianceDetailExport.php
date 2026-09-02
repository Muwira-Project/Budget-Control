<?php

namespace App\Exports;

use App\Models\MonitoringPeriod;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringVarianceDetailExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $rowCount = 0;

    public function __construct(private MonitoringPeriod $period) {}

    public function title(): string
    {
        return 'Variance Detail '.($this->period->nomor ?? '');
    }

    public function collection(): Collection
    {
        $rows = app(MonitoringPeriodService::class)->varianceDetailRows($this->period);

        if (! empty($rows)) {
            $totalBudget = (float) array_sum(array_column($rows, 'budget'));
            $totalActualOut = (float) array_sum(array_column($rows, 'actual_out'));
            $totalCashIn = (float) array_sum(array_column($rows, 'cash_in'));
            $totalAp = (float) array_sum(array_column($rows, 'ap_settlement'));
            $totalVariance = $totalBudget - $totalActualOut;

            $rows[] = [
                'budget_no' => '-',
                'po_number' => 'TOTAL KONSOLIDASI',
                'project' => 'SEMUA TRANSAKSI',
                'account' => 'TOTAL AKHIR',
                'type' => 'TOTAL',
                'pihak' => '-',
                'periode_week' => $this->period->nomor,
                'day_name' => '-',
                'budget_date' => '',
                'budget' => $totalBudget,
                'actual_date' => '',
                'actual_out' => $totalActualOut,
                'cash_in' => $totalCashIn,
                'ap_settlement' => $totalAp,
                'variance' => $totalVariance,
                'description' => 'Total Rencana Anggaran, Realisasi, Cash In, dan AP Pay periode '.($this->period->nomor ?? ''),
                '_is_total' => true,
            ];
        }

        $this->rowCount = count($rows);

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Budget No.',
            'PO Number',
            'Project',
            'Account',
            'Type',
            'Pihak',
            'Periode / Week',
            'Day',
            'Budget Date',
            'Budget',
            'Actual Date',
            'Actual Out',
            'Cash In (AR)',
            'AP Pay',
            'Variance',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        return [
            $row['budget_no'] ?? '-',
            $row['po_number'] ?? '-',
            $row['project'] ?? '',
            $row['account'] ?? '',
            $row['type'] ?? '',
            $row['pihak'] ?? '-',
            $row['periode_week'] ?? '',
            $row['day_name'] ?? '',
            $row['budget_date'] ?? '',
            (float) ($row['budget'] ?? 0),
            $row['actual_date'] ?? '',
            (float) ($row['actual_out'] ?? 0),
            (float) ($row['cash_in'] ?? 0),
            (float) ($row['ap_settlement'] ?? 0),
            (float) ($row['variance'] ?? 0),
            $row['description'] ?? '',
        ];
    }

    public function columnFormats(): array
    {
        $currencyFormat = '#,##0';

        return [
            'J' => $currencyFormat, // Budget
            'L' => $currencyFormat, // Actual Out
            'M' => $currencyFormat, // Cash In
            'N' => $currencyFormat, // AP Pay
            'O' => $currencyFormat, // Variance
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1; // +1 due to header row

        // Header styling
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Center align code/date/day/type columns
        $sheet->getStyle("A2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Right align monetary columns
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("L2:O{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Total row styling if rows exist
        if ($this->rowCount > 0) {
            $sheet->getStyle("A{$lastRow}:P{$lastRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => '0F172A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EFF6FF'], // Light Ice Blue
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '94A3B8'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['rgb' => '1E3A8A'],
                    ],
                ],
            ]);
            $sheet->getRowDimension($lastRow)->setRowHeight(24);
        }

        return [];
    }
}
