<?php

namespace App\Enums;

enum CashflowJenis: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    /**
     * Human-readable label for the jenis.
     */
    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Income',
            self::Keluar => 'Outcome',
        };
    }
}
