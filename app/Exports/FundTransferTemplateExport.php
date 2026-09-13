<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Excel template export for Fund Transfer import.
 */
class FundTransferTemplateExport implements FromArray
{
    /**
     * Template header + contoh baris + catatan untuk import fund transfer.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Date', 'From Cash Account', 'To Cash Account', 'Amount', 'Description'],
            ['2026-08-15', 'BANK-001', 'KAS-001', 5000000, 'Tarik tunai untuk kas kecil kantor'],
            ['2026-08-20', 'KAS-001', 'BANK-002', 10000000, 'Setor tunai ke rekening operasional'],
            [],
            ['NOTE: Template for Fund Transfer import.'],
            ['From Cash Account: use kode from Master Cash Account (source account).'],
            ['To Cash Account: use kode from Master Cash Account (destination account, must be different from source).'],
            ['Amount: numeric, positive value.'],
            ['Date format: YYYY-MM-DD.'],
            ['Fund transfers are internal liquidity movements between cash accounts (Non-Project).'],
        ];
    }
}
