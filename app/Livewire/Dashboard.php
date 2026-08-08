<?php

namespace App\Livewire;

use App\Models\Kategori;
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