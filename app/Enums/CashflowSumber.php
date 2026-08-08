<?php

namespace App\Enums;

enum CashflowSumber: string
{
    case PaymentRequest = 'payment_request';
    case Pendapatan = 'pendapatan';
    case PelunasanAr = 'pelunasan_ar';
    case PelunasanAp = 'pelunasan_ap';

    /**
     * Human-readable label for the sumber.
     */
    public function label(): string
    {
        return match ($this) {
            self::PaymentRequest => 'Payment Request',
            self::Pendapatan => 'Income',
            self::PelunasanAr => 'AR Settlement',
            self::PelunasanAp => 'AP Settlement',
        };
    }
}
