<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case InProgress = 'progress';
    case Done = 'done';
    case Cancelled = 'cancel';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether the project is finished (receivable auto-generated).
     */
    public function isDone(): bool
    {
        return $this === self::Done;
    }
}