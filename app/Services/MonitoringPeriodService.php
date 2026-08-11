<?php

namespace App\Services;

use App\Enums\PaymentJenis;
use App\Models\Akun;
use App\Models\BudgetPlanItem;
use App\Models\MonitoringPeriod;
use App\Models\NonProjectExpense;
use App\Models\NumberSequence;
use App\Models\Payment;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
        $budgets = $this->budgetPerAccount($period);
        $actuals = $this->actualPerAccount($period);
        $nonProject = $this->nonProjectPerAccount($period);

        if ($nonProject->isNotEmpty()) {
            $actuals = collect($actuals->keys()->merge($nonProject->keys())->unique()->mapWithKeys(
                fn (int $id): array => [$id => (float) ($actuals[$id] ?? 0) + (float) ($nonProject[$id] ?? 0)],
            ));
        }

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
    }

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
        return (float) $this->actualPerAccount($period)->sum() + $this->nonProjectTotal($period);
    }

    /**
     * Budget, actual, and variance totals for many periods at once.
     *
     * Uses batched queries instead of per-period queries (N+1) so the
     * monitoring index loads quickly even with many periods.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, array{budget: float, actual: float, variance: float}>
     */
    public function totalsForPeriods(Collection $periods): array
    {
        $periods = $periods->values();

        if ($periods->isEmpty()) {
            return [];
        }

        $firstIds = $this->firstPeriodIdsOfMonth($periods);

        $budgetRows = $this->bulkBudgetTotals($periods, $firstIds);
        $actualRows = $this->bulkActualTotals($periods);
        $inRows = $this->bulkActualInTotals($periods);
        $nonProjectRows = $this->bulkNonProjectTotals($periods);

        $result = [];

        foreach ($periods as $period) {
            $budget = (float) ($budgetRows[$period->id] ?? 0);
            $actual = (float) ($actualRows[$period->id] ?? 0);
            $result[$period->id] = [
                'budget' => $budget,
                'actual_in' => (float) ($inRows[$period->id] ?? 0),
                'actual' => $actual + (float) ($nonProjectRows[$period->id] ?? 0),
                'variance' => $budget - ($actual + (float) ($nonProjectRows[$period->id] ?? 0)),
            ];
        }

        return $result;
    }

    /**
     * Period ids that are the first period of the month for their scope.
     *
     * Legacy budget plan items without a date range are only counted in the
     * first period of each month to avoid double counting.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, int>
     */
    protected function firstPeriodIdsOfMonth(Collection $periods): array
    {
        $firstIds = [];

        $groups = $periods->groupBy(function (MonitoringPeriod $period) {
            return ($period->project_id ?? 'global').'|'.$period->tanggal_mulai->format('Y-m');
        });

        foreach ($groups as $key => $group) {
            [$projectScope, $yearMonth] = explode('|', $key);
            [$year, $month] = array_map('intval', explode('-', $yearMonth));

            $query = MonitoringPeriod::query()
                ->whereYear('tanggal_mulai', $year)
                ->whereMonth('tanggal_mulai', $month);

            if ($projectScope === 'global') {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', (int) $projectScope);
            }

            $earliestStart = $query->min('tanggal_mulai');

            if ($earliestStart === null) {
                continue;
            }

            $earliest = Carbon::parse($earliestStart);

            foreach ($group as $period) {
                if ($period->tanggal_mulai->format('Y-m-d') === $earliest->format('Y-m-d')) {
                    $firstIds[] = (int) $period->id;
                }
            }
        }

        return $firstIds;
    }

    /**
     * Total non-project expenses inside the period (global periods only).
     */
    public function nonProjectTotal(MonitoringPeriod $period): float
    {
        if ($period->project_id !== null) {
            return 0.0;
        }

        return (float) NonProjectExpense::query()
            ->whereDate('tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->sum('nominal');
    }

    /**
     * Non-project expense totals per account (global periods only).
     *
     * @return Collection<int, float>
     */
    protected function nonProjectPerAccount(MonitoringPeriod $period): Collection
    {
        if ($period->project_id !== null) {
            return collect();
        }

        return collect(NonProjectExpense::query()
            ->whereDate('tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->selectRaw('akun_id, SUM(nominal) AS total')
            ->groupBy('akun_id')
            ->pluck('total', 'akun_id')
            ->all());
    }

    /**
     * Sum non-project expenses across many periods with one query.
     *
     * Only periods without a project scope (global) receive these totals.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, float>
     */
    protected function bulkNonProjectTotals(Collection $periods): array
    {
        $rows = NonProjectExpense::query()
            ->join('monitoring_periods as mp', function ($join) {
                $join->on(function ($query) {
                    $query->whereColumn('non_project_expenses.tanggal', '>=', 'mp.tanggal_mulai')
                        ->whereColumn('non_project_expenses.tanggal', '<=', 'mp.tanggal_selesai')
                        ->whereNull('mp.project_id');
                });
            })
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->selectRaw('mp.id AS period_id, COALESCE(SUM(non_project_expenses.nominal), 0) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->mapWithKeys(fn ($total, $periodId) => [(int) $periodId => (float) $total])
            ->all();

        return $rows;
    }

    /**
     * Total actual (in) for the period: receivable settlements (cash in).
     */
    public function actualInTotal(MonitoringPeriod $period): float
    {
        return (float) Payment::query()
            ->where('jenis', PaymentJenis::Masuk)
            ->whereDate('tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->when($period->project_id, fn ($query) => $query->whereHas('receivable', fn ($q) => $q->where('project_id', $period->project_id)))
            ->sum('nominal');
    }

    /**
     * Sum receivable settlements (cash in) across many periods with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, float>
     */
    protected function bulkActualInTotals(Collection $periods): array
    {
        $rows = Payment::query()
            ->join('receivables as r', 'r.id', '=', 'payments.receivable_id')
            ->join('monitoring_periods as mp', function ($join) {
                $join->on(function ($query) {
                    $query->whereColumn('payments.tanggal', '>=', 'mp.tanggal_mulai')
                        ->whereColumn('payments.tanggal', '<=', 'mp.tanggal_selesai')
                        ->where(function ($scope) {
                            $scope->whereNull('mp.project_id')
                                ->orWhereColumn('r.project_id', '=', 'mp.project_id');
                        });
                });
            })
            ->where('payments.jenis', PaymentJenis::Masuk)
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->selectRaw('mp.id AS period_id, COALESCE(SUM(payments.nominal), 0) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->mapWithKeys(fn ($total, $periodId) => [(int) $periodId => (float) $total])
            ->all();

        return $rows;
    }

    /**
     * Sum realisasi across many periods with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, float>
     */
    protected function bulkActualTotals(Collection $periods): array
    {
        $rows = Realisasi::query()
            ->join('monitoring_periods as mp', function ($join) {
                $join->on(function ($query) {
                    $query->whereColumn('realisasi.tanggal', '>=', 'mp.tanggal_mulai')
                        ->whereColumn('realisasi.tanggal', '<=', 'mp.tanggal_selesai')
                        ->where(function ($scope) {
                            $scope->whereNull('mp.project_id')
                                ->orWhereColumn('realisasi.project_id', '=', 'mp.project_id');
                        });
                });
            })
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->selectRaw('mp.id AS period_id, SUM(realisasi.nominal) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->all();

        return array_map('floatval', $rows);
    }

    /**
     * Budget plan item totals per period with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @param  array<int, int>  $firstIds
     * @return array<int, float>
     */
    protected function bulkBudgetTotals(Collection $periods, array $firstIds): array
    {
        return BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->join('monitoring_periods as mp', function ($join) use ($firstIds) {
                $join->on(function ($query) use ($firstIds) {
                    $query->whereNotNull('budget_plan_items.tanggal_mulai')
                        ->whereNotNull('budget_plan_items.tanggal_selesai')
                        ->whereColumn('budget_plan_items.tanggal_mulai', '<=', 'mp.tanggal_selesai')
                        ->whereColumn('budget_plan_items.tanggal_selesai', '>=', 'mp.tanggal_mulai');

                    if ($firstIds !== []) {
                        $query->orWhere(function ($legacy) use ($firstIds) {
                            $legacy->whereIn('mp.id', $firstIds)
                                ->where(function ($nullRange) {
                                    $nullRange->whereNull('budget_plan_items.tanggal_mulai')
                                        ->orWhereNull('budget_plan_items.tanggal_selesai');
                                });
                        });
                    }
                });
            })
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->where(function ($scope) {
                $scope->whereNull('mp.project_id')
                    ->orWhereColumn('budget_plans.project_id', '=', 'mp.project_id');
            })
            ->selectRaw('mp.id AS period_id, SUM(budget_plan_items.nominal) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->mapWithKeys(fn ($total, $periodId) => [(int) $periodId => (float) $total])
            ->all();
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
    /**
     * Summary rows for the export (per period), filtered by project and search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summaryRows(?int $projectId = null, string $search = ''): array
    {
        $periods = MonitoringPeriod::query()
            ->with('project')
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($search !== '', fn ($query) => $query->where('nomor', 'like', '%'.$search.'%'))
            ->orderByDesc('tanggal_mulai')
            ->get();

        $totals = $this->totalsForPeriods($periods);

        return $periods->map(fn (MonitoringPeriod $period) => [
            'nomor' => $period->nomor,
            'periode' => $period->periode_label,
            'week' => $period->week,
            'month' => $period->month,
            'budget' => (float) ($totals[$period->id]['budget'] ?? 0),
            'actual_in' => (float) ($totals[$period->id]['actual_in'] ?? 0),
            'actual_out' => (float) ($totals[$period->id]['actual'] ?? 0),
            'variance' => (float) ($totals[$period->id]['variance'] ?? 0),
        ])->all();
    }

    protected function nextNomor(): string
    {
        do {
            $next = NumberSequence::next('monitoring_period');
            $nomor = 'MON-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        } while (MonitoringPeriod::where('nomor', $nomor)->exists());

        return $nomor;
    }
}
