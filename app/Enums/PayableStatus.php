<?php

namespace App\Enums;

enum PayableStatus: string
{
    case BelumBayar = 'belum_bayar';
    case Sebagian = 'sebagian';
    case Lunas = 'lunas';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Unpaid',
            self::Sebagian => 'Partial',
            self::Lunas => 'Paid',
        };
    }
}
