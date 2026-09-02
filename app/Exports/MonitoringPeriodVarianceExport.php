<?php

namespace App\Exports;

use App\Models\MonitoringPeriod;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonitoringPeriodVarianceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private MonitoringPeriod $period) {}

    public function collection(): Collection
    {
        return app(MonitoringPeriodService::class)->accountBreakdown($this->period);
    }

    public function headings(): array
    {
        return ['Account Code', 'Account Name', 'Budget', 'Actual', 'Variance'];
    }

    public function map($row): array
    {
        return [
            $row['akun']->kode_akun,
            $row['akun']->nama_akun,
            (float) $row['budget'],
            (float) $row['actual'],
            (float) $row['variance'],
        ];
    }
}
