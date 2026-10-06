<?php

namespace App\Services;

use App\Models\BudgetPlanItem;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;

class BudgetingSummaryService
{
    /** Calculate the existing header totals for the selected project scope. */
    public function calculate(mixed $projectId, bool $isAdmin, int $userId): array
    {
        $isNonProjectFilter = ($projectId === 'non-project' || $projectId === 'non_project');
        $project = (! $isNonProjectFilter && $projectId) ? Project::find($projectId) : null;

        if ($isNonProjectFilter) {
            $totalBudget = (float) ProjectAkun::query()
                ->whereNull('project_id')
                ->when(! $isAdmin, fn ($query) => $query->where('created_by', $userId))
                ->sum('budget');

            $totalAllocation = (float) ProjectAkun::query()
                ->whereNull('project_id')
                ->when(! $isAdmin, fn ($query) => $query->where('created_by', $userId))
                ->sum('allocation');

            $totalRealisasi = (float) Realisasi::query()->whereNull('project_id')->sum('nominal');
        } elseif ($project !== null) {
            $totalBudget = (float) BudgetPlanItem::query()
                ->whereHas('budgetPlan', fn ($query) => $query->where('project_id', $project->id))
                ->sum('nominal');

            $totalAllocation = (float) ProjectAkun::query()
                ->where('project_id', $project->id)
                ->when(! $isAdmin, fn ($query) => $query->where('created_by', $userId))
                ->sum('allocation');

            $totalRealisasi = (float) Realisasi::query()->where('project_id', $project->id)->sum('nominal');
        } else {
            $planItemsBudget = (float) BudgetPlanItem::query()->sum('nominal');
            $nonProjectBudget = (float) ProjectAkun::query()
                ->whereNull('project_id')
                ->when(! $isAdmin, fn ($query) => $query->where('created_by', $userId))
                ->sum('budget');

            $totalBudget = $planItemsBudget + $nonProjectBudget;

            $totalAllocation = (float) ProjectAkun::query()
                ->when(! $isAdmin, fn ($query) => $query->where('created_by', $userId))
                ->sum('allocation');

            $totalRealisasi = (float) Realisasi::query()->sum('nominal');
        }

        return [
            'total_budget' => $totalBudget,
            'total_allocation' => $totalAllocation,
            'total_realisasi' => $totalRealisasi,
        ];
    }
}
