<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class AkunTemplateExport implements FromArray
{
    /**
     * Template header for akun imports.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Account Code', 'Account Name', 'Type', 'Category'],
        ];
    }
}
