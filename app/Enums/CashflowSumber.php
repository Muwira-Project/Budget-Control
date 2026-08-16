<?php

namespace App\Enums;

enum CashflowSumber: string
{
    case Pendapatan = 'pendapatan';
    case PelunasanAr = 'pelunasan_ar';
    case PelunasanAp = 'pelunasan_ap';
    case PengeluaranLain = 'pengeluaran_lain';

    /**
     * Human-readable label for the sumber.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pendapatan => 'Income',
            self::PelunasanAr => 'AR Settlement',
            self::PelunasanAp => 'AP Settlement',
            self::PengeluaranLain => 'Other Expense',
        };
    }
}
