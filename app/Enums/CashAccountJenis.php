<?php

namespace App\Enums;

enum CashAccountJenis: string
{
    case Kas = 'kas';
    case Bank = 'bank';

    /**
     * Human-readable label for the jenis.
     */
    public function label(): string
    {
        return match ($this) {
            self::Kas => 'Cash',
            self::Bank => 'Bank',
        };
    }
}
