<?php

namespace App\Policies;

use App\Enums\KasStatus;
use App\Models\Cashflow;
use App\Models\User;

class CashflowPolicy
{
    /**
     * Whether the user can manage a draft cash entry (admin or owner).
     */
    public function manageDraft(User $user, Cashflow $cashflow): bool
    {
        return $user->isAdmin()
            || ($cashflow->status === KasStatus::Draft && $cashflow->created_by === $user->id);
    }
}
