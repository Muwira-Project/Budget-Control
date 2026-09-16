<?php

namespace App\Exports;

use App\Models\MasterType;
use Maatwebsite\Excel\Concerns\FromArray;

class MasterItemTemplateExport implements FromArray
{
    public function __construct(private MasterType $masterType)
    {
        $this->masterType->load('fields');
    }

    /**
     * Template header and sample rows for master item imports.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
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

        $prefix = strtoupper(substr($this->masterType->kode ?: 'ITEM', 0, 3));
        $row1 = [$prefix.'-001', 'Sample '.$this->masterType->nama.' 1'];
        $row2 = [$prefix.'-002', 'Sample '.$this->masterType->nama.' 2'];

        foreach ($this->masterType->fields as $field) {
            $val1 = match ($field->tipe) {
                'number' => 100,
                'date' => now()->format('Y-m-d'),
                default => 'Example '.$field->label.' 1',
            };
            $val2 = match ($field->tipe) {
                'number' => 250,
                'date' => now()->addDays(7)->format('Y-m-d'),
                default => 'Example '.$field->label.' 2',
            };
            $row1[] = $val1;
            $row2[] = $val2;
        }

        if ($this->masterType->flag_ar) {
            $row1[] = 'Yes';
            $row2[] = 'No';
        }

        if ($this->masterType->flag_ap) {
            $row1[] = 'Yes';
            $row2[] = 'No';
        }

        $row1[] = 'Active';
        $row2[] = 'Active';

        return [
            $headers,
            $row1,
            $row2,
        ];
    }
}
