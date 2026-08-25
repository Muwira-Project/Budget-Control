<?php

namespace App\Exports;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\Cashflow;
use App\Exports\Concerns\SanitizesSpreadsheetValues;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export Cash In/Out (Cashflow) records.
 */
class CashflowExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    /**
     * @param  array{jenis?: string|null, start_date?: string|null, end_date?: string|null, sumber?: string|null, cash_account_id?: int|null}  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * Query the cashflows with the applied filters.
     */
    public function query(): Builder
    {
        return Cashflow::query()
            ->with(['cashAccount', 'akun', 'project', 'pihakType', 'pihakItem', 'createdBy'])
            ->when($this->filters['jenis'] ?? null, fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->when(
                isset($this->filters['start_date']),
                fn ($query) => $query->whereDate('tanggal', '>=', $this->filters['start_date']),
            )
            ->when(
                isset($this->filters['end_date']),
                fn ($query) => $query->whereDate('tanggal', '<=', $this->filters['end_date']),
            )
            ->when($this->filters['sumber'] ?? null, fn ($query, $sumber) => $query->where('sumber', $sumber))
            ->when($this->filters['cash_account_id'] ?? null, fn ($query, $id) => $query->where('cash_account_id', $id))
            ->orderBy('tanggal')
            ->orderBy('id');
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Source',
            'Cash Account',
            'Account (COA)',
            'Project',
            'Party Type',
            'Party',
            'Amount',
            'Description',
            'Status',
            'Created By',
        ];
    }

    /**
     * Map a cashflow row to the report columns.
     *
     * @return array<int, mixed>
     */
    public function map($cashflow): array
    {
        $jenisLabel = match ($cashflow->jenis) {
            CashflowJenis::Masuk => 'Cash In',
            CashflowJenis::Keluar => 'Cash Out',
            default => $cashflow->jenis?->value,
        };

        $sumberLabel = match ($cashflow->sumber) {
            CashflowSumber::Pendapatan => 'Income',
            CashflowSumber::PelunasanAr => 'AR Settlement',
            CashflowSumber::PelunasanAp => 'AP Settlement',
            CashflowSumber::PengeluaranLain => 'Other Expense',
            default => $cashflow->sumber?->value,
        };

        return [
            $cashflow->tanggal->format('Y-m-d'),
            $this->spreadsheetValue($jenisLabel),
            $this->spreadsheetValue($sumberLabel),
            $this->spreadsheetValue($cashflow->cashAccount?->nama),
            $this->spreadsheetValue($cashflow->akun?->kode_akun.' - '.$cashflow->akun?->nama_akun),
            $this->spreadsheetValue($cashflow->project?->kode.' - '.$cashflow->project?->nama),
            $this->spreadsheetValue($cashflow->pihak_jenis ? ucfirst($cashflow->pihak_jenis) : ''),
            $this->spreadsheetValue($cashflow->pihak),
            (float) $cashflow->nominal,
            $this->spreadsheetValue($cashflow->keterangan),
            $this->spreadsheetValue($cashflow->status?->label() ?? ''),
            $this->spreadsheetValue($cashflow->createdBy?->name),
        ];
    }
}