<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
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
        return app(DashboardStatisticsService::class)->computeStatistics($startDate, $endDate);
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
     * Active cash accounts with current real balances.
     *
     * @return array<int, array{id: int, kode: string, nama: string, jenis: string, saldo: float}>
     */
    public function activeAccountsSummary(): array
    {
        return app(DashboardStatisticsService::class)->activeAccountsSummary();
    }
}
