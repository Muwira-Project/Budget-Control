<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\Realisasi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RealisasiExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    /**
     * @param  array{project_id?: int|null, start_date?: string|null, end_date?: string|null, status?: string|null}  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * Query the realisasi with the applied filters.
     */
    public function query(): Builder
    {
        return Realisasi::query()
            ->with(['project', 'akun', 'pihakType', 'pihakItem', 'kategori'])
            ->when($this->filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($this->filters['status'] ?? null, fn ($query, $status) => $query->whereHas('project', fn ($project) => $project->where('status', $status)))
            ->when(
                isset($this->filters['start_date']),
                fn ($query) => $query->whereDate('tanggal', '>=', $this->filters['start_date']),
            )
            ->when(
                isset($this->filters['end_date']),
                fn ($query) => $query->whereDate('tanggal', '<=', $this->filters['end_date']),
            )
            ->orderBy('tanggal');
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Project', 'Account', 'Category', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description'];
    }

    /**
     * Map a realisasi row to the report columns.
     *
     * @return array<int, mixed>
     */
    public function map($realisasi): array
    {
        return [
            $this->spreadsheetValue($realisasi->project->kode.' - '.$realisasi->project->nama),
            $this->spreadsheetValue($realisasi->akun->kode_akun.' - '.$realisasi->akun->nama_akun),
            $this->spreadsheetValue($realisasi->kategori?->nama),
            $realisasi->tanggal->format('Y-m-d'),
            $this->spreadsheetValue($realisasi->pihak),
            (float) $realisasi->nominal,
            $this->spreadsheetValue($realisasi->keterangan),
        ];
    }
}
