<?php

namespace App\Exports;

use App\Models\Payable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Export Payables (AP) to Excel/CSV.
 */
class PayableExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the query for payables to export.
     */
    public function query(): Builder
    {
        $query = Payable::query()->with(['project', 'pihakType', 'pihakItem']);

        // Project filter
        if (! empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        // Status filter
        if (! empty($this->filters['status'])) {
            match ($this->filters['status']) {
                'belum_bayar' => $query->whereRaw('nominal - nominal_dibayar > 0 AND status != ?', ['lunas']),
                'sebagian' => $query->whereRaw('nominal_dibayar > 0 AND nominal - nominal_dibayar > 0 AND status = ?', ['sebagian']),
                'lunas' => $query->where('status', 'lunas'),
                default => null,
            };
        }

        // Aging filter
        if (! empty($this->filters['aging'])) {
            $now = now()->toDateString();
            match ($this->filters['aging']) {
                'current' => $query->where(function ($q) use ($now) {
                    $q->whereNull('jatuh_tempo')->orWhere('jatuh_tempo', '>=', $now);
                }),
                '1_30' => $query->whereNotNull('jatuh_tempo')
                    ->where('jatuh_tempo', '<', $now)
                    ->whereDate('jatuh_tempo', '>=', now()->subDays(30)->toDateString()),
                '31_60' => $query->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', now()->subDays(30)->toDateString())
                    ->whereDate('jatuh_tempo', '>=', now()->subDays(60)->toDateString()),
                '61_90' => $query->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', now()->subDays(60)->toDateString())
                    ->whereDate('jatuh_tempo', '>=', now()->subDays(90)->toDateString()),
                'over_90' => $query->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', now()->subDays(90)->toDateString()),
                default => null,
            };
        }

        // Date range filter
        if (! empty($this->filters['date_from'])) {
            $query->whereDate('tanggal', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('tanggal', '<=', $this->filters['date_to']);
        }

        // Amount range filter (on nominal - total invoice)
        if (isset($this->filters['amount_min'])) {
            $query->where('nominal', '>=', $this->filters['amount_min']);
        }
        if (isset($this->filters['amount_max'])) {
            $query->where('nominal', '<=', $this->filters['amount_max']);
        }

        return $query->orderByDesc('tanggal');
    }

    /**
     * Map each payable to an array for export.
     *
     * @param  Payable  $payable
     * @return array<int, string|int|float|null>
     */
    public function map($payable): array
    {
        return [
            $payable->project?->kode ?? '-',
            $payable->project?->nama ?? '-',
            $payable->nomor_invoice ?? '-',
            $payable->tanggal?->format('Y-m-d'),
            $payable->jatuh_tempo?->format('Y-m-d'),
            $payable->pihakType?->nama ?? '-',
            $payable->pihakItem?->nama ?? '-',
            $payable->nominal,
            $payable->nominal_dibayar,
            $payable->nominal - $payable->nominal_dibayar,
            $payable->status->label(),
            $payable->keterangan ?? '-',
        ];
    }

    /**
     * Return the column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Invoice No.',
            'Date',
            'Due Date',
            'Party Type',
            'Party',
            'Amount',
            'Paid',
            'Outstanding',
            'Status',
            'Description',
        ];
    }
}