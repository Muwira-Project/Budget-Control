<?php

namespace App\Enums;

enum JenisAkun: string
{
    case Pendapatan = 'pendapatan';
    case Pengeluaran = 'pengeluaran';

    /**
     * Human-readable label for the jenis akun.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pendapatan => 'Income',
            self::Pengeluaran => 'Outcome',
        };
    }
}
