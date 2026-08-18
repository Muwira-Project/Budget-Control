<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Whether the user can access the backup module.
     */
    public function accessBackups(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can manage user accounts.
     */
    public function manageUsers(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can manage monitoring periods.
     */
    public function manageMonitoring(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can approve/reject budget allocations.
     */
    public function approveAllocations(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can release a held receivable.
     */
    public function releaseReceivables(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can manage settlements (approve/reject voids, direct delete).
     */
    public function manageSettlements(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can access the trash (restore/force-delete soft-deleted rows).
     */
    public function accessTrash(User $user): bool
    {
        return $user->isAdmin();
    }
}
