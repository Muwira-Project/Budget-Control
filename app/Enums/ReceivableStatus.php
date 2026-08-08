<?php

namespace App\Enums;

enum ReceivableStatus: string
{
    case BelumDibayar = 'belum_dibayar';
    case Sebagian = 'sebagian';
    case Lunas = 'lunas';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Unpaid',
            self::Sebagian => 'Partial',
            self::Lunas => 'Paid',
        };
    }
}
