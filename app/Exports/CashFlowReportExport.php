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
 * Supports detailed bank ledger when an account is selected, or account summary.
 */
class CashFlowReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    private array $data;

    private bool $isDetailed;

    public function __construct(
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
        private readonly ?int $cashAccountId = null,
    ) {
        $reportService = app(ReportService::class);
        $this->isDetailed = $cashAccountId !== null;

        if ($this->isDetailed) {
            $this->data = $reportService->cashFlowDetail($startDate, $endDate, $cashAccountId);
        } else {
            $this->data = $reportService->cashFlow($startDate, $endDate);
        }
    }

    /**
     * Sheet title.
     */
    public function title(): string
    {
        if ($this->isDetailed) {
            $acc = $this->data['account'] ?? [];
            return substr(($acc['kode'] ?? 'Detail').' - '.($acc['nama'] ?? 'Ledger'), 0, 31);
        }

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
            $period = ' ('.($this->startDate ?? 'Awal').' s/d '.($this->endDate ?? 'Kini').')';
        }

        if ($this->isDetailed) {
            $acc = $this->data['account'] ?? [];
            $bankInfo = ($acc['kode'] ?? '').' - '.($acc['nama'] ?? '').$period;

            return [
                'Tanggal',
                'No. Voucher / Ref',
                'Tipe Transaksi',
                'Sumber / Kategori',
                'Pihak Terkait',
                'Keterangan ['.$bankInfo.']',
                'Penerimaan (Masuk)',
                'Pengeluaran (Keluar)',
                'Saldo Berjalan',
            ];
        }

        return [
            'Kode Rekening',
            'Nama Rekening / Bank',
            'Jenis Akun'.$period,
            'Saldo Awal',
            'Cash In',
            'Cash Out',
            'Transfer In',
            'Transfer Out',
            'Saldo Akhir',
        ];
    }

    /**
     * Build data rows.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];

        if ($this->isDetailed) {
            // Row 1: Saldo Awal
            $rows[] = [
                $this->startDate ?? '-',
                '-',
                'SALDO AWAL',
                '-',
                '-',
                'Saldo awal sebelum periode',
                0.0,
                0.0,
                (float) ($this->data['saldo_awal'] ?? 0),
            ];

            // Transaction rows
            foreach ($this->data['transactions'] as $tx) {
                $rows[] = [
                    $tx['tanggal_fmt'] ?? $tx['tanggal'],
                    $tx['ref_no'],
                    $tx['jenis'],
                    $tx['sumber'],
                    $tx['pihak'],
                    $tx['keterangan'],
                    (float) $tx['masuk'],
                    (float) $tx['keluar'],
                    (float) $tx['saldo_berjalan'],
                ];
            }

            // Separator
            $rows[] = ['', '', '', '', '', '', '', '', ''];

            // Total row
            $rows[] = [
                '',
                'TOTAL',
                '',
                '',
                '',
                'Total Mutasi & Saldo Akhir',
                (float) ($this->data['total_masuk'] ?? 0),
                (float) ($this->data['total_keluar'] ?? 0),
                (float) ($this->data['saldo_akhir'] ?? 0),
            ];

            return $rows;
        }

        // Summary of all accounts
        foreach ($this->data['rows'] as $row) {
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

        // Separator
        $rows[] = ['', '', '', '', '', '', '', '', ''];

        // Total row
        $totals = $this->data['totals'];
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
     * Apply styles.
     */
    public function styles(Worksheet $sheet): array
    {
        $rowCount = $this->isDetailed
            ? count($this->data['transactions']) + 4 // heading + saldo awal + tx + separator + total
            : count($this->data['rows']) + 3;

        // Heading row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        if ($this->isDetailed) {
            // Saldo Awal row styling
            $sheet->getStyle('A2:I2')->applyFromArray([
                'font' => ['bold' => true, 'italic' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f8fafc']],
            ]);

            // Number formatting for amount columns G, H, I
            $sheet->getStyle("G2:I{$rowCount}")->getNumberFormat()->setFormatCode('#,##0.00');
        } else {
            // Number formatting for amount columns D-I
            $sheet->getStyle("D2:I{$rowCount}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Total row styling
        $sheet->getStyle("A{$rowCount}:I{$rowCount}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f1f5f9']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        return [];
    }
}
