<?php

namespace App\Services;

use App\Enums\AllocationStatus;
use App\Enums\KasStatus;
use App\Enums\ProjectJenis;
use App\Enums\ProjectStatus;
use App\Models\BudgetPlanItem;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Kategori;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Cache TTL in seconds (10 minutes).
     */
    private const CACHE_TTL = 600;

    /**
     * Get the summary statistics for the dashboard.
     *
     * @return array<string, int|float|array<string, float>>
     */
    public function statistics(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = $this->getCacheKey($startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            return $this->computeStatistics($startDate, $endDate);
        });
    }

    /**
     * Compute statistics without caching (internal use).
     *
     * @return array<string, int|float|array<string, float>>
     */
    private function computeStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $totalBudget = $this->budgetForDateRange($startDate, $endDate);
        $totalAllocation = (float) ProjectAkun::where('status', AllocationStatus::Approved)->sum('allocation');

        $realisasiQuery = Realisasi::query()
            ->when($startDate, fn($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('tanggal', '<=', $endDate));

        $totalRealisasi = (float) (clone $realisasiQuery)->sum('nominal');

        $projects = Project::query()
            ->withSum('projectAkuns as budget_total', 'budget')
            ->withSum(['projectAkuns as approved_total' => fn(Builder $query) => $query->where('status', AllocationStatus::Approved)], 'allocation')
            ->withSum(['realisasi as realisasi_total' => fn(Builder $query) => $this->applyDateRange($query, $startDate, $endDate)], 'nominal')
            ->orderBy('kode')
            ->get();

        $cashflow = app(CashflowService::class)->statistics($startDate, $endDate);

        $outstandingAr = (float) Receivable::selectRaw('COALESCE(SUM(nominal - nominal_dibayar), 0) as total')->value('total');
        $outstandingAp = (float) Payable::selectRaw('COALESCE(SUM(nominal - nominal_dibayar), 0) as total')->value('total');

        $arBreakdown = $this->arBreakdown($startDate, $endDate);
        $apBreakdown = $this->apBreakdown($startDate, $endDate);

        $profitProjects = $projects->map(fn(Project $project) => [
            'kode' => $project->kode,
            'nama' => $project->nama,
            'project_id' => $project->id,
            'nilai' => $project->nilai_total,
            'realisasi' => $project->realisasi_total ?? 0,
            'profit' => $project->nilai_total - (float) ($project->realisasi_total ?? 0),
        ])->sortByDesc('profit')->values()->all();

        $chartBudgetRealisasi = $projects
            ->sortByDesc('approved_total')
            ->take(10)
            ->values()
            ->map(fn(Project $project) => [
                'kode' => $project->kode,
                'nama' => $project->nama,
                'project_id' => $project->id,
                'budget' => (float) ($project->approved_total ?? 0),
                'realisasi' => (float) ($project->realisasi_total ?? 0),
            ])
            ->all();

        $projectBudget = (float) ProjectAkun::whereNotNull('project_id')->sum('budget');
        $nonProjectBudget = (float) ProjectAkun::whereNull('project_id')->sum('budget');
        $projectAllocation = (float) ProjectAkun::whereNotNull('project_id')->where('status', AllocationStatus::Approved)->sum('allocation');
        $nonProjectAllocation = (float) ProjectAkun::whereNull('project_id')->where('status', AllocationStatus::Approved)->sum('allocation');

        $realisasiProject = (float) (clone $realisasiQuery)->whereNotNull('project_id')->sum('nominal');
        $realisasiNonProject = (float) (clone $realisasiQuery)->whereNull('project_id')->sum('nominal');

        $nonProjectAllocations = ProjectAkun::query()
            ->with(['akun', 'pihakItem'])
            ->whereNull('project_id')
            ->where('status', AllocationStatus::Approved)
            ->orderByDesc('allocation')
            ->take(6)
            ->get()
            ->map(fn (ProjectAkun $pa) => [
                'id' => $pa->id,
                'akun_kode' => $pa->akun?->kode_akun ?? '-',
                'akun_nama' => $pa->akun?->nama_akun ?? '-',
                'type' => $pa->type,
                'type_label' => $pa->type_label,
                'display_name' => $pa->display_name,
                'budget' => (float) $pa->budget,
                'allocation' => (float) $pa->allocation,
                'realisasi' => (float) $pa->total_realisasi,
                'sisa' => (float) $pa->sisa_allocation,
            ])
            ->all();

        return [
            'total_projects' => $projects->count(),
            'projects_barang' => $projects->where('jenis', ProjectJenis::Barang)->count(),
            'projects_jasa' => $projects->where('jenis', ProjectJenis::Jasa)->count(),
            'total_budget' => $totalBudget,
            'total_allocation' => $totalAllocation,
            'total_realisasi' => $totalRealisasi,
            'total_sisa' => $totalBudget - $totalRealisasi,
            'project_budget' => $projectBudget,
            'non_project_budget' => $nonProjectBudget,
            'project_allocation' => $projectAllocation,
            'non_project_allocation' => $nonProjectAllocation,
            'realisasi_project' => $realisasiProject,
            'realisasi_non_project' => $realisasiNonProject,
            'non_project_allocations' => $nonProjectAllocations,
            'total_nilai' => $projects->sum(fn(Project $project) => $project->nilai_total),
            'total_pajak' => $projects->sum(fn(Project $project) => $project->nilai_pajak),
            'persentase' => $totalBudget > 0 ? round(($totalRealisasi / $totalBudget) * 100, 1) : 0,
            'kategori_breakdown' => $this->kategoriBreakdown($realisasiQuery),
            'opening_balance' => (float) ($cashflow['opening_balance'] ?? 0),
            'cash_in' => (float) ($cashflow['total_masuk'] ?? 0),
            'cash_out' => (float) ($cashflow['total_keluar'] ?? 0),
            'saldo_kas' => (float) ($cashflow['saldo'] ?? 0),
            'current_balance' => (float) ($cashflow['saldo_rekening'] ?? 0),
            'cash_accounts' => $this->activeAccountsSummary(),
            'outstanding_ar' => $outstandingAr,
            'outstanding_ap' => $outstandingAp,
            'ar_breakdown' => $arBreakdown,
            'ap_breakdown' => $apBreakdown,
            'total_profit' => array_sum(array_column($profitProjects, 'profit')),
            'profit_projects' => $profitProjects,
            'chart_budget_realisasi' => $chartBudgetRealisasi,
        ];
    }

    /**
     * Projects ready for submission (status = draft).
     * Returns array of project summary data.
     *
     * @return array<array{kode: string, nama: string, project_id: int, status: string, division: string|null, nilai_total: float}>
     */
    public function projectsToSubmit(): array
    {
        return Project::query()
            ->with('division')
            ->where('status', ProjectStatus::Draft)
            ->orderBy('kode')
            ->get()
            ->map(fn(Project $project) => [
                'kode' => $project->kode,
                'nama' => $project->nama,
                'project_id' => $project->id,
                'status' => $project->status->label(),
                'division' => $project->division?->nama,
                'nilai_total' => $project->nilai_total,
            ])
            ->values()
            ->all();
    }

    /**
     * Projects needing revision (status = revisi).
     * Returns array of project summary data.
     *
     * @return array<array{kode: string, nama: string, project_id: int, status: string, division: string|null, nilai_total: float}>
     */
    public function projectsToRevisi(): array
    {
        return Project::query()
            ->with('division')
            ->where('status', ProjectStatus::Revisi)
            ->orderBy('kode')
            ->get()
            ->map(fn(Project $project) => [
                'kode' => $project->kode,
                'nama' => $project->nama,
                'project_id' => $project->id,
                'status' => $project->status->label(),
                'division' => $project->division?->nama,
                'nilai_total' => $project->nilai_total,
            ])
            ->values()
            ->all();
    }

    /**
     * Counts for submit and revisi widgets.
     *
     * @return array{submit_count: int, revisi_count: int}
     */
    public function submitRevisiCounts(): array
    {
        return [
            'submit_count' => Project::query()->where('status', ProjectStatus::Draft)->count(),
            'revisi_count' => Project::query()->where('status', ProjectStatus::Revisi)->count(),
        ];
    }

    /**
     * Generate cache key based on date range and the current data version.
     *
     * The version is bumped by clearCache() so every cache key is invalidated
     * the moment any underlying transaction data changes, regardless of the
     * selected date range.
     */
    private function getCacheKey(?string $startDate = null, ?string $endDate = null): string
    {
        $version = (int) Cache::get('dashboard_stats_version', 0);

        if ($startDate === null && $endDate === null) {
            return "dashboard_stats:v{$version}:all";
        }

        $start = $startDate ?? 'null';
        $end = $endDate ?? 'null';

        return "dashboard_stats:v{$version}:{$start}:{$end}";
    }

    /**
     * Invalidate the dashboard cache.
     *
     * Call after creating, updating, or deleting any transaction data that
     * appears on the dashboard (realisasi, cashflow, receivable, payable,
     * project, project account, kategori, company settings).
     */
    public static function clearCache(): void
    {
        Cache::forever('dashboard_stats_version', ((int) Cache::get('dashboard_stats_version', 0)) + 1);
    }

    /**
     * Budget total for the selected range, using the same overlapping budget-plan
     * logic as the monitoring period to keep dashboard and monitoring aligned.
     */
    protected function budgetForDateRange(?string $startDate, ?string $endDate): float
    {
        if ($startDate === null && $endDate === null) {
            return (float) ProjectAkun::sum('budget');
        }

        $rangeStart = $startDate ?? $endDate ?? now()->toDateString();
        $rangeEnd = $endDate ?? $startDate ?? now()->toDateString();

        $rangeBudget = (float) BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereNotNull('budget_plan_items.tanggal_mulai')
                    ->whereNotNull('budget_plan_items.tanggal_selesai')
                    ->whereDate('budget_plan_items.tanggal_mulai', '<=', $rangeEnd)
                    ->whereDate('budget_plan_items.tanggal_selesai', '>=', $rangeStart)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('budget_plan_items.tanggal_mulai')
                            ->orWhereNull('budget_plan_items.tanggal_selesai');
                    });
            })
            ->sum('budget_plan_items.nominal');

        $nonProjectBudget = (float) ProjectAkun::whereNull('project_id')->sum('budget');

        return $rangeBudget > 0 ? ($rangeBudget + $nonProjectBudget) : (float) ProjectAkun::sum('budget');
    }

    /**
     * Apply the date range filter to a query.
     */
    protected function applyDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        return $query->when($startDate, fn(Builder $q) => $q->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn(Builder $q) => $q->whereDate('tanggal', '<=', $endDate));
    }

    /**
     * Total realisasi grouped by the six budgeting categories.
     *
     * @return array<string, float>
     */
    protected function kategoriBreakdown(Builder $realisasiQuery): array
    {
        $totals = Kategori::orderBy('kode')->get()
            ->mapWithKeys(fn(Kategori $kategori) => [$kategori->nama => 0.0])
            ->all();

        foreach ((clone $realisasiQuery)
                ->join('kategoris', 'kategoris.id', '=', 'realisasi.kategori_id')
                ->selectRaw('kategoris.nama as nama, COALESCE(SUM(realisasi.nominal), 0) as total')
                ->groupBy('kategoris.nama')
                ->get() as $row
        ) {
            $totals[$row->nama] = (float) $row->total;
        }

        return $totals;
    }

    /**
     * AR Breakdown by category (billed, unbilled, inprogress) with grand total.
     * Respects the dashboard date range filter.
     *
     * @return array<string, array{nominal: float, paid: float, outstanding: float, count: int}>
     */
    protected function arBreakdown(?string $startDate = null, ?string $endDate = null): array
    {
        $categories = ['billed', 'unbilled', 'inprogress'];

        $breakdown = [];
        $grandTotal = ['nominal' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'count' => 0];

        foreach ($categories as $category) {
            $query = Receivable::query()->whereHas('project', function ($q) use ($category) {
                match ($category) {
                    'billed' => $q->where('status', ProjectStatus::Done)->whereNotNull('po_number'),
                    'unbilled' => $q->where('status', ProjectStatus::Done)->whereNull('po_number'),
                    'inprogress' => $q->where('status', '!=', ProjectStatus::Done),
                    default => $q,
                };
            });

            // Apply date range filter on receivable tanggal
            if ($startDate) {
                $query->whereDate('tanggal', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('tanggal', '<=', $endDate);
            }

            $aggregates = $query->selectRaw(
                'COALESCE(SUM(nominal), 0) as total_nominal,
                 COALESCE(SUM(nominal_dibayar), 0) as total_paid,
                 COALESCE(SUM(nominal - nominal_dibayar), 0) as total_outstanding,
                 COUNT(*) as total_count'
            )->first();

            $breakdown[$category] = [
                'nominal' => (float) $aggregates->total_nominal,
                'paid' => (float) $aggregates->total_paid,
                'outstanding' => (float) $aggregates->total_outstanding,
                'count' => (int) $aggregates->total_count,
            ];

            $grandTotal['nominal'] += $breakdown[$category]['nominal'];
            $grandTotal['paid'] += $breakdown[$category]['paid'];
            $grandTotal['outstanding'] += $breakdown[$category]['outstanding'];
            $grandTotal['count'] += $breakdown[$category]['count'];
        }

        $breakdown['total'] = $grandTotal;

        return $breakdown;
    }

    /**
     * AP Breakdown by MasterType (Vendor, Supplier, Mandor, Investor) with grand total.
     * Respects the dashboard date range filter.
     *
     * @return array<string, array{nominal: float, paid: float, outstanding: float, count: int}>
     */
    protected function apBreakdown(?string $startDate = null, ?string $endDate = null): array
    {
        $masterTypes = MasterType::where('flag_ap', true)->orderBy('kode')->get(['id', 'kode', 'nama']);

        $breakdown = [];
        $grandTotal = ['nominal' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'count' => 0];

        foreach ($masterTypes as $masterType) {
            $query = Payable::query()->where('pihak_type_id', $masterType->id);

            // Apply date range filter on payable tanggal
            if ($startDate) {
                $query->whereDate('tanggal', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('tanggal', '<=', $endDate);
            }

            $aggregates = $query->selectRaw(
                'COALESCE(SUM(nominal), 0) as total_nominal,
                 COALESCE(SUM(nominal_dibayar), 0) as total_paid,
                 COALESCE(SUM(nominal - nominal_dibayar), 0) as total_outstanding,
                 COUNT(*) as total_count'
            )->first();

            $breakdown[$masterType->kode] = [
                'label' => $masterType->nama,
                'nominal' => (float) $aggregates->total_nominal,
                'paid' => (float) $aggregates->total_paid,
                'outstanding' => (float) $aggregates->total_outstanding,
                'count' => (int) $aggregates->total_count,
            ];

            $grandTotal['nominal'] += $breakdown[$masterType->kode]['nominal'];
            $grandTotal['paid'] += $breakdown[$masterType->kode]['paid'];
            $grandTotal['outstanding'] += $breakdown[$masterType->kode]['outstanding'];
            $grandTotal['count'] += $breakdown[$masterType->kode]['count'];
        }

        $breakdown['total'] = array_merge($grandTotal, ['label' => 'Total']);

        return $breakdown;
    }

    /**
     * Active cash accounts with current real balances.
     *
     * @return array<int, array{id: int, kode: string, nama: string, jenis: string, saldo: float}>
     */
    public function activeAccountsSummary(): array
    {
        $accounts = CashAccount::where('status', 'active')->orderBy('kode')->get();
        $accountIds = $accounts->pluck('id')->all();
        $balances = CashAccount::balances($accountIds);

        return $accounts->map(fn (CashAccount $acc) => [
            'id' => $acc->id,
            'kode' => $acc->kode,
            'nama' => $acc->nama,
            'jenis' => $acc->jenis?->label() ?? 'Kas/Bank',
            'saldo' => (float) ($balances[$acc->id] ?? 0.0),
        ])->all();
    }
}
