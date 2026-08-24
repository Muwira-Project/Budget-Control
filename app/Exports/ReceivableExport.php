<?php

namespace App\Exports;

use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Export Receivables (AR) to Excel/CSV.
 */
class ReceivableExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the query for receivables to export.
     */
    public function query(): Builder
    {
        $query = Receivable::query()->with(['project', 'pihakType', 'pihakItem']);

        // Project filter
        if (! empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        // Status filter
        if (! empty($this->filters['status'])) {
            match ($this->filters['status']) {
                'belum_dibayar' => $query->whereRaw('nominal - nominal_dibayar > 0 AND status != ?', ['lunas']),
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

        // AR Category filter (billed, unbilled, inprogress)
        if (! empty($this->filters['ar_category'])) {
            $query->whereHas('project', function ($q) {
                match ($this->filters['ar_category']) {
                    'billed' => $q->where('status', 'done')->whereNotNull('po_number'),
                    'unbilled' => $q->where('status', 'done')->whereNull('po_number'),
                    'inprogress' => $q->where('status', '!=', 'done'),
                    default => null,
                };
            });
        }

        // PO Number filter
        if (! empty($this->filters['po_number'])) {
            $query->whereHas('project', function ($q) {
                $q->where('po_number', 'like', '%' . $this->filters['po_number'] . '%');
            });
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
     * Map each receivable to an array for export.
     *
     * @param  Receivable  $receivable
     * @return array<int, string|int|float|null>
     */
    public function map($receivable): array
    {
        return [
            $receivable->project?->kode ?? '-',
            $receivable->project?->nama ?? '-',
            $receivable->project?->po_number ?? '-',
            $receivable->nomor_invoice ?? '-',
            $receivable->tanggal?->format('Y-m-d'),
            $receivable->jatuh_tempo?->format('Y-m-d'),
            $receivable->pihakType?->nama ?? '-',
            $receivable->pihakItem?->nama ?? '-',
            $receivable->nominal,
            $receivable->nominal_dibayar,
            $receivable->nominal - $receivable->nominal_dibayar,
            $receivable->status->label(),
            $receivable->keterangan ?? '-',
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
            'PO Number',
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