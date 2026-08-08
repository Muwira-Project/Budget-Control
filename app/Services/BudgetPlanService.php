<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Models\BudgetPlan;
use App\Models\Project;
use App\Models\ProjectAkun;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetPlanService
{
    /**
     * Create a new budget plan with its rincian per akun.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BudgetPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = BudgetPlan::create([
                'project_id' => $data['project_id'],
                'periode' => $data['periode'],
                'estimasi_pendapatan' => $data['estimasi_pendapatan'],
                'estimasi_biaya' => $this->sumItems($data['items']),
                'target_laba' => $data['target_laba'] ?? 0,
            ]);

            $this->syncItems($plan, $data['items']);

            return $plan->refresh();
        });
    }

    /**
     * Update an existing budget plan together with its rincian per akun.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(BudgetPlan $plan, array $data): BudgetPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'project_id' => $data['project_id'],
                'periode' => $data['periode'],
                'estimasi_pendapatan' => $data['estimasi_pendapatan'],
                'estimasi_biaya' => $this->sumItems($data['items']),
                'target_laba' => $data['target_laba'] ?? 0,
            ]);

            $this->syncItems($plan, $data['items']);

            return $plan->refresh();
        });
    }

    /**
     * Delete a budget plan (items are removed by the database cascade).
     */
    public function delete(BudgetPlan $plan): void
    {
        $plan->delete();
    }

    /**
     * Create draft allocations for every item of the plan (idempotent).
     *
     * Returns the number of new draft allocations created. Existing
     * project-akun rows are left untouched (their status is preserved).
     */
    public function createAllocationsFromPlan(BudgetPlan $plan): int
    {
        $created = 0;

        foreach ($plan->items as $item) {
            $allocation = ProjectAkun::firstOrCreate(
                ['project_id' => $plan->project_id, 'akun_id' => $item->akun_id],
                [
                    'budget' => $item->nominal,
                    'allocation' => $item->nominal,
                    'status' => AllocationStatus::Draft,
                ],
            );

            if ($allocation->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Rebuild the rincian of every budget plan for the project from the
     * approved allocations, keeping estimasi_biaya & target_laba in sync
     * with the approved total (target_laba may be negative when costs
     * exceed the estimated revenue).
     */
    public function syncFromApprovedAllocations(Project $project): void
    {
        $approved = $project->projectAkuns()
            ->where('status', AllocationStatus::Approved)
            ->get();

        foreach ($project->budgetPlans()->get() as $plan) {
            $plan->items()->delete();

            foreach ($approved as $allocation) {
                $plan->items()->create([
                    'akun_id' => $allocation->akun_id,
                    'nominal' => $allocation->budget,
                ]);
            }

            $estimasiBiaya = (float) $plan->items()->sum('nominal');

            $plan->update([
                'estimasi_biaya' => $estimasiBiaya,
                'target_laba' => (float) $plan->estimasi_pendapatan - $estimasiBiaya,
            ]);
        }
    }

    /**
     * List budget plans, optionally filtered by project and search query.
     */
    public function paginate(?Project $project = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return BudgetPlan::query()
            ->with(['project', 'items'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($search !== '', fn ($query) => $query->whereHas('project', fn ($projectQuery) => $projectQuery
                ->where('kode', 'like', '%'.$search.'%')
                ->orWhere('nama', 'like', '%'.$search.'%')))
            ->orderByDesc('periode')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Sum the nominal values of the rincian items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function sumItems(array $items): float
    {
        return (float) array_sum(array_map(fn ($item) => (float) ($item['nominal'] ?? 0), $items));
    }

    /**
     * Replace the rincian items of a budget plan.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(BudgetPlan $plan, array $items): void
    {
        $plan->items()->delete();

        foreach ($items as $item) {
            $plan->items()->create([
                'akun_id' => $item['akun_id'],
                'nominal' => $item['nominal'],
                'tanggal_mulai' => $item['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $item['tanggal_selesai'] ?? null,
            ]);
        }
    }
}
