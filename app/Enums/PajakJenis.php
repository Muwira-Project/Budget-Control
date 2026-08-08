<?php

namespace App\Enums;

enum PajakJenis: string
{
    case Ppn = 'ppn';
    case Pph = 'pph';

    /**
     * Human-readable label for the jenis pajak.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ppn => 'PPN',
            self::Pph => 'PPh',
        };
    }
}
