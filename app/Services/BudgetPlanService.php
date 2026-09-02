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
            $project = Project::find($data['project_id']);
            $projectCode = $project?->kode ?? 'NP';
            $periode = $data['periode'] ?? '0000-00';

            // Generate nomor: BP/PROJECT_CODE/PERIODE/SEQUENCE
            $lastPlan = BudgetPlan::where('project_id', $data['project_id'])
                ->where('periode', $data['periode'])
                ->latest('id')
                ->first();
            $sequence = ($lastPlan?->id ?? 0) + 1;
            $nomor = 'BP/'.$projectCode.'/'.$periode.'/'.$sequence;

            $plan = BudgetPlan::create([
                'project_id' => $data['project_id'],
                'periode' => $data['periode'],
                'nomor' => $nomor,
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
     * When the project already has approved allocations, the rincian is
     * rebuilt from those allocations (single source of truth: allocations
     * win), so the plan can never diverge from the approved budget.
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

            $hasApprovedAllocations = ProjectAkun::query()
                ->where('project_id', $plan->project_id)
                ->where('status', AllocationStatus::Approved)
                ->exists();

            if ($hasApprovedAllocations) {
                $this->syncFromApprovedAllocations($plan->project);
            } else {
                $this->syncItems($plan, $data['items']);
            }

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
            ->get(['akun_id', 'budget']);

        $itemRows = $approved->map(fn ($allocation) => [
            'akun_id' => $allocation->akun_id,
            'nominal' => $allocation->budget,
        ])->all();

        $estimasiBiaya = (float) $approved->sum('budget');

        foreach ($project->budgetPlans()->get() as $plan) {
            $plan->items()->delete();
            $plan->items()->createMany($itemRows);

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
