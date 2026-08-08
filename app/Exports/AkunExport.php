<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\Akun;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AkunExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * Query the master akuns for the report.
     */
    public function query(): Builder
    {
        return Akun::query()
            ->with('kategori')
            ->orderBy('jenis_akun')
            ->orderBy('kode_akun');
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Account Code', 'Account Name', 'Type', 'Category'];
    }

    /**
     * Map an akun row to the report columns.
     *
     * @return array<int, mixed>
     */
    public function map($akun): array
    {
        return [
            $this->spreadsheetValue($akun->kode_akun),
            $this->spreadsheetValue($akun->nama_akun),
            $akun->jenis_akun->label(),
            $this->spreadsheetValue($akun->kategori?->nama),
        ];
    }
}
