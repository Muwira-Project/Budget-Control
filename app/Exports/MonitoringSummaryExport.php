<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private array $rows) {}

    /**
     * Return the summary rows.
     */
    public function collection(): Collection
    {
        return collect($this->rows);
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Number', 'Period', 'Week', 'Month', 'Budget', 'Actual In', 'Actual Out', 'Variance'];
    }

    /**
     * Map a summary row to the report columns.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row['nomor'],
            $row['periode'],
            $row['week'],
            $row['month'],
            (float) $row['budget'],
            (float) $row['actual_in'],
            (float) $row['actual_out'],
            (float) $row['variance'],
        ];
    }
}
