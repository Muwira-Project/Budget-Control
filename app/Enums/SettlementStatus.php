<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case Active = 'active';
    case PendingCancel = 'pending_cancel';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PendingCancel => 'Pending Cancellation',
            self::Cancelled => 'Cancelled',
        };
    }
}