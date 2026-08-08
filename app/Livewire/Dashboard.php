<?php

namespace App\Livewire;

use App\Enums\PaymentRequestStatus;
use App\Models\Activity;
use App\Models\Kategori;
use App\Models\MonitoringPeriod;
use App\Models\PaymentRequest;
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

    /**
     * Payment request summary counts by status (existing data only).
     *
     * @return array{pending: int, approved: int, rejected: int}
     */
    #[Computed]
    public function paymentRequestSummary(): array
    {
        return [
            'pending' => PaymentRequest::where('status', PaymentRequestStatus::Waiting)->count(),
            'approved' => PaymentRequest::where('status', PaymentRequestStatus::Approved)->count(),
            'rejected' => PaymentRequest::where('status', PaymentRequestStatus::Rejected)->count(),
        ];
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
            ->with(['akun', 'kategori', 'vendor', 'supplier', 'mandor', 'investor'])
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
            ->with(['akun', 'project', 'vendor', 'supplier', 'mandor', 'investor'])
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