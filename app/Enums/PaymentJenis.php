<?php

namespace App\Enums;

enum PaymentJenis: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    /**
     * Human-readable label for the jenis.
     */
    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Income (AR Settlement)',
            self::Keluar => 'Expense (AP Settlement)',
        };
    }
}
