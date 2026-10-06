<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class CashAccountTemplateExport implements FromArray
{
    public function array(): array
    {
        return [['Code', 'Name', 'Type', 'Opening Balance', 'Default', 'Status', 'Description']];
    }
}
