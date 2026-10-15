<?php

namespace App\Services;

use App\Models\BudgetPlanItem;
use Illuminate\Support\Collection;

class BudgetingRowsService
{
    /**
     * Merge allocation records and budget-plan items using the same project/account keys.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildMergedRows(Collection $allocations, Collection $planItems): array
    {
        $byKey = [];

        foreach ($allocations as $allocation) {
            if ($allocation->project_id === null) {
                $key = 'nonproj-'.$allocation->akun_id.'-alloc-'.$allocation->id;
            } else {
                $key = 'proj-'.$allocation->project_id.'-'.$allocation->akun_id;
            }

            $budgetingNumber = null;
            if ($allocation->project_id !== null) {
                $planItem = BudgetPlanItem::whereHas('budgetPlan', function ($query) use ($allocation) {
                    $query->where('project_id', $allocation->project_id);
                })->where('akun_id', $allocation->akun_id)->first();
                if ($planItem) {
                    $budgetingNumber = $planItem->budgetPlan->nomor;
                }
            }

            $byKey[$key] = [
                'project' => $allocation->project,
                'is_non_project' => $allocation->project_id === null,
                'akun' => $allocation->akun,
                'budget' => (float) $allocation->budget,
                'allocation' => $allocation,
                'plan' => null,
                'budgeting_number' => $budgetingNumber,
            ];
        }

        foreach ($planItems as $item) {
            $key = 'proj-'.$item->budgetPlan->project_id.'-'.$item->akun_id;

            if (isset($byKey[$key])) {
                $byKey[$key]['plan'] = $item;
                $byKey[$key]['budgeting_number'] = $item->budgetPlan->nomor;
            } else {
                $byKey[$key] = [
                    'project' => $item->budgetPlan->project,
                    'is_non_project' => false,
                    'akun' => $item->akun,
                    'budget' => (float) $item->nominal,
                    'allocation' => null,
                    'plan' => $item,
                    'budgeting_number' => $item->budgetPlan->nomor,
                ];
            }
        }

        return collect($byKey)->values()->all();
    }

    /** Apply the existing search fields to merged budgeting rows. */
    public function applySearchFilter(array $rows, string $search): array
    {
        if ($search === '') {
            return $rows;
        }

        $needle = strtolower(trim($search));

        return array_filter($rows, function (array $row) use ($needle) {
            $project = $row['project'];
            $akun = $row['akun'];
            $allocation = $row['allocation'];
            $partyOrCustom = $allocation ? ($allocation->display_name ?? '') : '';
            $typeLabel = ($allocation && $row['is_non_project']) ? ($allocation->type_label ?? '') : '';
            $bNumber = $row['budgeting_number'] ?? '';

            $haystack = strtolower(
                trim(
                    ($project?->kode ?? ($row['is_non_project'] ? 'non-project non project non-proyek non proyek' : '')).' '.
                    ($project?->nama ?? ($row['is_non_project'] ? 'Non-Project' : '')).' '.
                    ($akun?->kode_akun ?? '').' '.
                    ($akun?->nama_akun ?? '').' '.
                    $partyOrCustom.' '.
                    $typeLabel.' '.
                    $bNumber
                )
            );

            return str_contains($haystack, $needle);
        });
    }

    /** Sort rows by project code then account code, with non-project rows first. */
    public function sortRows(array $rows): array
    {
        usort($rows, function (array $a, array $b) {
            if ($a['is_non_project'] !== $b['is_non_project']) {
                return $a['is_non_project'] ? -1 : 1;
            }

            $projectCodeA = strtolower($a['project']?->kode ?? '');
            $projectCodeB = strtolower($b['project']?->kode ?? '');
            if ($projectCodeA !== $projectCodeB) {
                return strcmp($projectCodeA, $projectCodeB);
            }

            return strcmp(
                strtolower($a['akun']?->kode_akun ?? ''),
                strtolower($b['akun']?->kode_akun ?? '')
            );
        });

        return array_values($rows);
    }
}
