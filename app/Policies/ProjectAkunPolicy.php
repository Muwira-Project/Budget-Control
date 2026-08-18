<?php

namespace App\Policies;

use App\Enums\AllocationStatus;
use App\Models\ProjectAkun;
use App\Models\User;

class ProjectAkunPolicy
{
    /**
     * Whether the user can manage a draft allocation (admin or owner).
     */
    public function manageDraft(User $user, ProjectAkun $allocation): bool
    {
        return $user->isAdmin()
            || ($allocation->status === AllocationStatus::Draft && $allocation->created_by === $user->id);
    }

    /**
     * Whether the user can view the allocation list (admin sees all).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can approve/reject an allocation.
     */
    public function approve(User $user, ProjectAkun $allocation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user can delete an allocation.
     */
    public function delete(User $user, ProjectAkun $allocation): bool
    {
        return $user->isAdmin()
            || ($allocation->status === AllocationStatus::Draft && $allocation->created_by === $user->id);
    }
}
