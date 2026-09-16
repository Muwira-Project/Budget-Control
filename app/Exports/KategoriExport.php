<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KategoriExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    public function __construct(private ?string $search = null) {}

    /**
     * Query the categories for the report.
     */
    public function query(): Builder
    {
        return Kategori::query()
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('kode');
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Code', 'Name'];
    }

    /**
     * Map a category row to the report columns.
     *
     * @param  Kategori  $kategori
     * @return array<int, mixed>
     */
    public function map($kategori): array
    {
        return [
            $this->spreadsheetValue($kategori->kode),
            $this->spreadsheetValue($kategori->nama),
        ];
    }
}
