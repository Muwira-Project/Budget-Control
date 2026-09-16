<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class KategoriTemplateExport implements FromArray
{
    /**
     * Template header and sample rows for category imports.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Code', 'Name'],
            ['KAT-001', 'Material'],
            ['KAT-002', 'Upah Kerja'],
            ['KAT-003', 'Operasional & Overhead'],
        ];
    }
}
