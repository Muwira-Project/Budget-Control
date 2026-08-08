<?php

namespace App\Enums;

enum AllocationStatus: string
{
    case Draft = 'draft';
    case Waiting = 'waiting';
    case Approved = 'approved';
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
            self::Rejected => 'Rejected',
        };
    }
}
