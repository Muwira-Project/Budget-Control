<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case InProgress = 'progress';
    case Done = 'done';
    case Cancelled = 'cancelled';
    case Revisi = 'revisi';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In Progress',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
            self::Revisi => 'Revisi',
        };
    }

    /**
     * Whether the project is finished (receivable auto-generated).
     */
    public function isDone(): bool
    {
        return $this === self::Done;
    }

    /**
     * Define allowed status transitions.
     * Key = current status, Value = array of allowed next statuses.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        $allowed = [
            self::Draft->value => [self::InProgress->value, self::Cancelled->value],
            self::InProgress->value => [self::Done->value, self::Revisi->value, self::Cancelled->value],
            self::Revisi->value => [self::InProgress->value, self::Cancelled->value],
            self::Done->value => [self::Revisi->value, self::Cancelled->value],
            self::Cancelled->value => [], // Terminal state - no transitions allowed
        ];

        return in_array($newStatus->value, $allowed[$this->value] ?? []);
    }

    /**
     * Get all allowed next statuses for the current status.
     *
     * @return array<int, self>
     */
    public function getAllowedTransitions(): array
    {
        $allowed = [
            self::Draft->value => [self::InProgress, self::Cancelled],
            self::InProgress->value => [self::Done, self::Revisi, self::Cancelled],
            self::Revisi->value => [self::InProgress, self::Cancelled],
            self::Done->value => [self::Revisi, self::Cancelled],
            self::Cancelled->value => [],
        ];

        return $allowed[$this->value] ?? [];
    }
}
