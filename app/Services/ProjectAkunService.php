<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Enums\CashflowSumber;
use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\Project;
use App\Models\ProjectAkun;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectAkunService
{
    /**
     * Create a new allocation request as a draft.
     *
     * project_id may be null for a non-project (operational) allocation.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectAkun
    {
        return ProjectAkun::create([
            'project_id' => $data['project_id'] ?? null,
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
            'project_id' => $data['project_id'] ?? null,
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
     *
     * Project allocations sync their budget plan; non-project allocations
     * generate a cashflow draft that then flows through the normal cash
     * approval chain (draft → submit → approve → post).
     */
    public function approve(ProjectAkun $allocation): ProjectAkun
    {
        $allocation->update([
            'status' => AllocationStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($allocation->project_id === null) {
            $this->createCashflowFromNonProjectAllocation($allocation);
        } else {
            app(BudgetPlanService::class)->syncFromApprovedAllocations($allocation->project);
        }

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

        if ($allocation->project_id !== null) {
            app(BudgetPlanService::class)->syncFromApprovedAllocations($allocation->project);
        }

        return $allocation->refresh();
    }

    /**
     * Create a draft cashflow (keluar) for an approved non-project allocation.
     *
     * The cashflow carries the COA akun of the allocation and enters the
     * standard cash workflow (draft → submit → approve → post). Posting the
     * cashflow issues the voucher and (via the cashflow) the actual/realisasi
     * row with project_id = null for the global scope.
     */
    public function createCashflowFromNonProjectAllocation(ProjectAkun $allocation): ?Cashflow
    {
        if ($allocation->project_id !== null) {
            return null;
        }

        return app(CashflowService::class)->create([
            'tanggal' => now()->toDateString(),
            'jenis' => 'keluar',
            'sumber' => CashflowSumber::PengeluaranLain,
            'cash_account_id' => CashAccount::defaultId(),
            'akun_id' => $allocation->akun_id,
            'nominal' => $allocation->allocation,
            'keterangan' => 'Non-project budget allocation #'.$allocation->id.' — '.($allocation->akun?->nama_akun ?? ''),
            'status' => KasStatus::Draft,
        ]);
    }

    /**
     * List allocations, optionally filtered by project and status.
     * Non-project rows (project_id = null) are included unless a specific
     * project is selected.
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
