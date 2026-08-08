<?php

namespace App\Enums;

enum PaymentRequestStatus: string
{
    case Draft = 'draft';
    case Waiting = 'waiting';
    case Approved = 'approved';
    case Paid = 'paid';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Waiting => 'Pending Approval',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }
}
