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
            'type' => $data['type'] ?? 'other_outcome',
            'pihak_type_id' => $data['pihak_type_id'] ?? null,
            'pihak_item_id' => $data['pihak_item_id'] ?? null,
            'payable_id' => $data['payable_id'] ?? null,
            'receivable_id' => $data['receivable_id'] ?? null,
            'custom_name' => $data['custom_name'] ?? null,
            'outstanding_balance' => $data['outstanding_balance'] ?? null,
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
            'type' => $data['type'] ?? $allocation->type,
            'pihak_type_id' => $data['pihak_type_id'] ?? $allocation->pihak_type_id,
            'pihak_item_id' => $data['pihak_item_id'] ?? $allocation->pihak_item_id,
            'payable_id' => $data['payable_id'] ?? $allocation->payable_id,
            'receivable_id' => $data['receivable_id'] ?? $allocation->receivable_id,
            'custom_name' => $data['custom_name'] ?? $allocation->custom_name,
            'outstanding_balance' => $data['outstanding_balance'] ?? $allocation->outstanding_balance,
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

        $description = 'Non-project budget allocation #'.$allocation->id.' — '.($allocation->akun?->nama_akun ?? '');
        if ($allocation->type !== 'other_outcome') {
            $description .= ' ['.$allocation->type_label.']';
        }
        if ($allocation->display_name !== '-') {
            $description .= ' — '.$allocation->display_name;
        }

        return app(CashflowService::class)->create([
            'tanggal' => now()->toDateString(),
            'jenis' => 'keluar',
            'sumber' => CashflowSumber::PengeluaranLain,
            'cash_account_id' => CashAccount::defaultId(),
            'akun_id' => $allocation->akun_id,
            'nominal' => $allocation->allocation,
            'keterangan' => $description,
            'status' => KasStatus::Draft,
        ]);
    }

    /**
     * Calculate outstanding balance for a party item (AP/AR).
     */
    public function calculateOutstandingBalance(string $type, int $pihakItemId): float
    {
        return app(OutstandingBalanceService::class)->calculate($type, $pihakItemId);
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
