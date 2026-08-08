<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Models\Project;
use App\Models\ProjectAkun;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectAkunService
{
    /**
     * Create a new allocation request as a draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectAkun
    {
        return ProjectAkun::create([
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'budget' => $data['budget'],
            'allocation' => $data['allocation'],
            'status' => AllocationStatus::Draft,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update an existing allocation request.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectAkun $allocation, array $data): ProjectAkun
    {
        $allocation->update([
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'budget' => $data['budget'],
            'allocation' => $data['allocation'],
        ]);

        return $allocation->refresh();
    }

    /**
     * Delete an allocation request.
     */
    public function delete(ProjectAkun $allocation): void
    {
        $allocation->delete();
    }

    /**
     * Submit a draft allocation for approval.
     */
    public function submit(ProjectAkun $allocation): ProjectAkun
    {
        $allocation->update([
            'status' => AllocationStatus::Waiting,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $allocation->refresh();
    }

    /**
     * Approve a waiting allocation.
     */
    public function approve(ProjectAkun $allocation): ProjectAkun
    {
        $allocation->update([
            'status' => AllocationStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(BudgetPlanService::class)->syncFromApprovedAllocations($allocation->project);

        return $allocation->refresh();
    }

    /**
     * Reject a waiting allocation.
     */
    public function reject(ProjectAkun $allocation): ProjectAkun
    {
        $allocation->update([
            'status' => AllocationStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(BudgetPlanService::class)->syncFromApprovedAllocations($allocation->project);

        return $allocation->refresh();
    }

    /**
     * List allocations, optionally filtered by project and status.
     */
    public function paginate(?Project $project = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return ProjectAkun::query()
            ->with(['project', 'akun'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('project_id')
            ->orderBy('akun_id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
