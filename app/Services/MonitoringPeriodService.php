<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

use App\Models\Akun;
use App\Models\BudgetPlanItem;
use App\Models\MonitoringPeriod;
use App\Models\NumberSequence;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonitoringPeriodService
{
    /**
     * Create a new monitoring period.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MonitoringPeriod
    {
        return MonitoringPeriod::create([
            'nomor' => $this->nextNomor(),
            'project_id' => $data['project_id'] ?? null,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);
    }

    /**
     * Update an existing monitoring period.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MonitoringPeriod $period, array $data): MonitoringPeriod
    {
        $period->update([
            'project_id' => $data['project_id'] ?? null,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);

        return $period->refresh();
    }

    /**
     * Delete a monitoring period.
     */
    public function delete(MonitoringPeriod $period): void
    {
        $period->delete();
    }

    /**
     * List monitoring periods, optionally filtered by project and search.
     */
    public function paginate(?int $projectId = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return MonitoringPeriod::query()
            ->with('project')
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($search !== '', fn ($query) => $query->where('nomor', 'like', '%'.$search.'%'))
            ->orderByDesc('tanggal_mulai')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Per-account monitoring rows: budget, actual, and variance within the period.
     *
     * @return Collection<int, array{akun: Akun, budget: float, actual: float, variance: float}>
     */
    public function accountBreakdown(MonitoringPeriod $period): Collection
    {
        $cacheKey = "monitoring.breakdown.{$period->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($period) {
            $budgets = $this->budgetPerAccount($period);
            $actuals = $this->actualPerAccount($period);

            $akunIds = $budgets->keys()->merge($actuals->keys())->unique()->values();

            return Akun::query()
                ->whereIn('id', $akunIds)
                ->orderBy('kode_akun')
                ->get()
                ->map(function (Akun $akun) use ($budgets, $actuals) {
                    $budget = (float) ($budgets[$akun->id] ?? 0);
                    $actual = (float) ($actuals[$akun->id] ?? 0);

                    return [
                        'akun' => $akun,
                        'budget' => $budget,
                        'actual' => $actual,
                        'variance' => $budget - $actual,
                    ];
                })
                ->values();
        });

    /**
     * Total budget for the period across all accounts.
     */
    public function budgetTotal(MonitoringPeriod $period): float
    {
        return (float) $this->budgetPerAccount($period)->sum();
    }

    /**
     * Total actual for the period across all accounts.
     */
    public function actualTotal(MonitoringPeriod $period): float
    {
        return (float) $this->actualPerAccount($period)->sum();
    }

    /**
     * Budget per account from budget plan items overlapping the period.
     *
     * @return Collection<int, float>
     */
    protected function budgetPerAccount(MonitoringPeriod $period): Collection
    {
        $includeLegacy = ! $this->hasEarlierPeriodInSameMonth($period);

        $query = BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->where(function ($query) use ($period, $includeLegacy) {
                $query->whereNotNull('budget_plan_items.tanggal_mulai')
                    ->whereNotNull('budget_plan_items.tanggal_selesai')
                    ->whereDate('budget_plan_items.tanggal_mulai', '<=', $period->tanggal_selesai->format('Y-m-d'))
                    ->whereDate('budget_plan_items.tanggal_selesai', '>=', $period->tanggal_mulai->format('Y-m-d'));

                if ($includeLegacy) {
                    $query->orWhere(function ($legacy) {
                        $legacy->whereNull('budget_plan_items.tanggal_mulai')
                            ->orWhereNull('budget_plan_items.tanggal_selesai');
                    });
                }
            })
            ->when($period->project_id, fn ($query) => $query->where('budget_plans.project_id', $period->project_id))
            ->selectRaw('budget_plan_items.akun_id, SUM(budget_plan_items.nominal) as total')
            ->groupBy('budget_plan_items.akun_id');

        return collect($query->pluck('total', 'akun_id')->all());
    }

    /**
     * Whether another monitoring period for the same scope starts earlier in the same month.
     *
     * Legacy budget plan items without a date range are only counted in the
     * first period of each month to avoid double counting.
     */
    protected function hasEarlierPeriodInSameMonth(MonitoringPeriod $period): bool
    {
        return MonitoringPeriod::query()
            ->where('project_id', $period->project_id)
            ->whereYear('tanggal_mulai', $period->tanggal_mulai->year)
            ->whereMonth('tanggal_mulai', $period->tanggal_mulai->month)
            ->whereDate('tanggal_mulai', '<', $period->tanggal_mulai->format('Y-m-d'))
            ->exists();
    }

    /**
     * Actual per account from realisasi within the period.
     *
     * @return Collection<int, float>
     */
    protected function actualPerAccount(MonitoringPeriod $period): Collection
    {
        $query = Realisasi::query()
            ->whereDate('tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->when($period->project_id, fn ($query) => $query->where('project_id', $period->project_id))
            ->selectRaw('akun_id, SUM(nominal) as total')
            ->groupBy('akun_id');

        return collect($query->pluck('total', 'akun_id')->all());
    }

    /**
     * Generate the next monitoring period number for the current year.
     */
    protected function nextNomor(): string
    {
        $next = NumberSequence::next('monitoring_period');

        return 'MON-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}


