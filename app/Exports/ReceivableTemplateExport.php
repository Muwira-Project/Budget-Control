<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Dynamic Excel template for Receivable (AR) import.
 * Supports both "with project code" and "without project code" modes.
 */
class ReceivableTemplateExport implements FromArray
{
    public function __construct(protected bool $useProjectCode = true) {}

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        if ($this->useProjectCode) {
            return [['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description']];
        }

        return [['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description']];
    }
}
