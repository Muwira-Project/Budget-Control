<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Payable (AP) import.
 */
class PayableTemplateExport implements FromArray
{
    protected bool $useProjectCode = true;

    public function __construct(bool $useProjectCode = true)
    {
        $this->useProjectCode = $useProjectCode;
    }

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        if ($this->useProjectCode) {
            return [['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description']];
        }

        return [['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description']];
    }
}
