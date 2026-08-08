<?php

namespace App\Enums;

enum ProjectJenis: string
{
    case Barang = 'barang';
    case Jasa = 'jasa';

    /**
     * Human-readable label for the jenis.
     */
    public function label(): string
    {
        return match ($this) {
            self::Barang => 'Goods',
            self::Jasa => 'Services',
        };
    }

    /**
     * Default tax percentage: 11% PPN for goods, 2% for services.
     */
    public function pajakDefault(): float
    {
        return match ($this) {
            self::Barang => 11.0,
            self::Jasa => 2.0,
        };
    }
}
