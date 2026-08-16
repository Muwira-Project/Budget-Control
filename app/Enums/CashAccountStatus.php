<?php

namespace App\Enums;

enum CashAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}