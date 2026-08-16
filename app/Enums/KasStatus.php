<?php

namespace App\Enums;

enum KasStatus: string
{
    case Draft = 'draft';
    case Waiting = 'waiting';
    case Approved = 'approved';
    case Posted = 'posted';
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
            self::Posted => 'Posted',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether the entry is posted (affects ledgers / balances).
     */
    public function isPosted(): bool
    {
        return $this === self::Posted;
    }

    /**
     * Whether the entry is waiting for admin approval.
     */
    public function isWaiting(): bool
    {
        return $this === self::Waiting;
    }

    /**
     * Whether the entry is still a draft.
     */
    public function isDraft(): bool
    {
        return $this === self::Draft;
    }
}
