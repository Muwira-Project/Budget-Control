<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\ProjectAkun;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AkunVsRealisasiExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    /**
     * @param  array{project_id?: int|null, start_date?: string|null, end_date?: string|null, status?: string|null}  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * Query the project-akun allocations with the applied filters.
     */
    public function query(): Builder
    {
        return ProjectAkun::query()
            ->where('status', 'approved')
            ->with(['project', 'akun', 'realisasi' => fn ($query) => $this->applyDateRange($query)])
            ->when($this->filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($this->filters['status'] ?? null, fn ($query, $status) => $query->whereHas('project', fn ($project) => $project->where('status', $status)))
            ->orderBy('project_id')
            ->orderBy('akun_id');
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Project', 'Account Code', 'Account Name', 'Budget', 'Allocation', 'Total Actual', 'Remaining (Variance)', 'Percentage (%)'];
    }

    /**
     * Map an allocation row to the report columns.
     *
     * @return array<int, mixed>
     */
    public function map($allocation): array
    {
        $budget = (float) $allocation->budget;
        $totalRealisasi = (float) $allocation->realisasi->where('akun_id', $allocation->akun_id)->sum('nominal');
        $percentage = $budget > 0 ? round(($totalRealisasi / $budget) * 100, 1) : 0;

        return [
            $this->spreadsheetValue($allocation->project->kode.' - '.$allocation->project->nama),
            $this->spreadsheetValue($allocation->akun->kode_akun),
            $this->spreadsheetValue($allocation->akun->nama_akun),
            $budget,
            (float) $allocation->allocation,
            $totalRealisasi,
            (float) $allocation->budget - $totalRealisasi,
            $percentage,
        ];
    }

    /**
     * Apply the date range filter to the realisasi relation query.
     */
    protected function applyDateRange($query): void
    {
        $query->when(
            isset($this->filters['start_date']),
            fn ($q) => $q->whereDate('tanggal', '>=', $this->filters['start_date']),
        )->when(
            isset($this->filters['end_date']),
            fn ($q) => $q->whereDate('tanggal', '<=', $this->filters['end_date']),
        );
    }
}
