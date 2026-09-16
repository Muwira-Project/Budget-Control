<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\MasterItem;
use App\Models\MasterType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MasterItemExport implements FromCollection, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    public function __construct(
        private MasterType $masterType,
        private ?string $search = null
    ) {
        $this->masterType->load('fields');
    }

    /**
     * @return Collection<int, MasterItem>
     */
    public function collection(): Collection
    {
        return MasterItem::query()
            ->where('master_type_id', $this->masterType->id)
            ->when($this->search, fn ($query) => $query->where(function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('kode')
            ->get();
    }

    /**
     * Column headings for the report.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headers = ['Code', 'Name'];

        foreach ($this->masterType->fields as $field) {
            $headers[] = $field->label;
        }

        if ($this->masterType->flag_ar) {
            $headers[] = 'AR';
        }

        if ($this->masterType->flag_ap) {
            $headers[] = 'AP';
        }

        $headers[] = 'Status';

        return $headers;
    }

    /**
     * Map a master item row to the report columns.
     *
     * @param  MasterItem  $item
     * @return array<int, mixed>
     */
    public function map($item): array
    {
        $row = [
            $this->spreadsheetValue($item->kode),
            $this->spreadsheetValue($item->nama),
        ];

        $data = (array) ($item->data ?? []);
        foreach ($this->masterType->fields as $field) {
            $val = $data[(string) $field->id] ?? '';
            $row[] = $this->spreadsheetValue($val !== null ? (string) $val : '');
        }

        if ($this->masterType->flag_ar) {
            $row[] = $item->flag_ar ? 'Yes' : 'No';
        }

        if ($this->masterType->flag_ap) {
            $row[] = $item->flag_ap ? 'Yes' : 'No';
        }

        $row[] = $item->aktif ? 'Active' : 'Inactive';

        return $row;
    }
}
