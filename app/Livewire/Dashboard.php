<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Models\Realisasi;
use App\Services\DashboardService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $selectedProjectId = null;

    public ?string $selectedCategoryName = null;

    /**
     * Summary statistics for the dashboard, optionally within a date range.
     *
     * @return array<string, int|float|array<string, float>>
     */
    #[Computed]
    public function statistics(): array
    {
        return app(DashboardService::class)->statistics($this->startDate, $this->endDate);
    }

    /**
     * Projects ready for submission (draft).
     *
     * @return array<array{kode: string, nama: string, project_id: int, status: string, division: string|null, nilai_total: float}>
     */
    #[Computed]
    public function projectsToSubmit(): array
    {
        return app(DashboardService::class)->projectsToSubmit();
    }

    /**
     * Projects needing revision (revisi).
     *
     * @return array<array{kode: string, nama: string, project_id: int, status: string, division: string|null, nilai_total: float}>
     */
    #[Computed]
    public function projectsToRevisi(): array
    {
        return app(DashboardService::class)->projectsToRevisi();
    }

    /**
     * Submit/Revisi counts for widget badges.
     *
     * @return array{submit_count: int, revisi_count: int}
     */
    #[Computed]
    public function submitRevisiCounts(): array
    {
        return app(DashboardService::class)->submitRevisiCounts();
    }

    /**
     * Whether the selected date range is invalid (start after end).
     */
    #[Computed]
    public function dateRangeInvalid(): bool
    {
        return $this->startDate !== null
            && $this->endDate !== null
            && $this->startDate > $this->endDate;
    }

    /**
     * The latest (active) monitoring period for the welcome header.
     */
    #[Computed]
    public function activePeriod(): ?MonitoringPeriod
    {
        return MonitoringPeriod::latest('tanggal_mulai')->first();
    }

    /**
     * A representative active project (latest) for the welcome header.
     */
    #[Computed]
    public function activeProject(): ?Project
    {
        return Project::latest()->first();
    }

    /**
     * Latest activity timestamp for the welcome header.
     */
    #[Computed]
    public function lastUpdate()
    {
        $latest = Activity::latest()->first();

        return $latest?->created_at ?? now();
    }

    /**
     * Recent activities across the application (existing activity log data).
     */
    #[Computed]
    public function recentActivities(): Collection
    {
        return Activity::query()
            ->with('user')
            ->latest()
            ->take(8)
            ->get();
    }

    public function showProjectDetail(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
        $this->selectedCategoryName = null;
    }

    public function showCategoryDetail(string $categoryName): void
    {
        $this->selectedCategoryName = $categoryName;
        $this->selectedProjectId = null;
    }

    public function closeDetails(): void
    {
        $this->selectedProjectId = null;
        $this->selectedCategoryName = null;
    }

    #[Computed]
    public function selectedProject(): ?Project
    {
        return $this->selectedProjectId
            ? Project::withSum('projectAkuns as budget_total', 'budget')->find($this->selectedProjectId)
            : null;
    }

    /**
     * Realisasi rows for the selected project inside the active date range.
     */
    #[Computed]
    public function projectRealisations(): Collection
    {
        if ($this->selectedProjectId === null) {
            return collect();
        }

        return Realisasi::query()
            ->with(['akun', 'kategori', 'pihakType', 'pihakItem'])
            ->where('project_id', $this->selectedProjectId)
            ->when($this->startDate, fn ($query) => $query->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('tanggal', '<=', $this->endDate))
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Realisasi rows for the selected category inside the active date range.
     */
    #[Computed]
    public function categoryRealisations(): Collection
    {
        if ($this->selectedCategoryName === null) {
            return collect();
        }

        return Realisasi::query()
            ->with(['akun', 'project', 'pihakType', 'pihakItem'])
            ->whereHas('kategori', fn ($query) => $query->where('nama', $this->selectedCategoryName))
            ->when($this->startDate, fn ($query) => $query->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('tanggal', '<=', $this->endDate))
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Render the dashboard.
     */
    public function render()
    {
        return view('livewire.dashboard');
    }
}
