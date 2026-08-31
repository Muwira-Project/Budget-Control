<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Project;
use App\Models\Realisasi;
use App\Services\ProjectService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $search = '';

    public ?string $filterPeriode = null;

    public ?string $filterStatus = null;

    public ?string $filterPic = null;

    public ?int $selectedProjectId = null;

    /**
     * Reset pagination when filters change.
     */
    public function updatedFilterPeriode(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when status filter changes.
     */
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when PIC filter changes.
     */
    public function updatedFilterPic(): void
    {
        $this->resetPage();
    }

    /**
     * Export projects to Excel.
     */
    public function export(?string $periode = null)
    {
        $this->redirectRoute('imports.projects.export', ['periode' => $periode], navigate: true);
    }

    /**
     * The paginated list of projects.
     */
    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return Project::query()
            ->with(['division'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filterPeriode, fn ($query) => $query->where('periode', $this->filterPeriode))
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterPic, fn ($query) => $query->where('pic', 'like', '%'.$this->filterPic.'%'))
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Available periods for filter dropdown.
     */
    #[Computed]
    public function availablePeriodes(): Collection
    {
        return Project::query()
            ->select('periode')
            ->whereNotNull('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode');
    }

    /**
     * Available PICs for filter dropdown.
     */
    #[Computed]
    public function availablePics(): Collection
    {
        return Project::query()
            ->select('pic')
            ->whereNotNull('pic')
            ->where('pic', '!=', '')
            ->distinct()
            ->orderBy('pic')
            ->pluck('pic');
    }

    /**
     * Available project statuses for filter dropdown.
     */
    #[Computed]
    public function availableStatuses(): array
    {
        return [
            '' => 'All Statuses',
            ProjectStatus::Draft->value => ProjectStatus::Draft->label(),
            ProjectStatus::InProgress->value => ProjectStatus::InProgress->label(),
            ProjectStatus::Revisi->value => ProjectStatus::Revisi->label(),
            ProjectStatus::Done->value => ProjectStatus::Done->label(),
            ProjectStatus::Cancelled->value => ProjectStatus::Cancelled->label(),
        ];
    }

    public function showProjectDetail(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
    }

    public function closeDetails(): void
    {
        $this->selectedProjectId = null;
    }

    #[Computed]
    public function selectedProject(): ?Project
    {
        return $this->selectedProjectId
            ? Project::with(['division', 'projectAkuns' => fn ($q) => $q->sum('budget')])->find($this->selectedProjectId)
            : null;
    }

    /**
     * Realisasi rows for the selected project.
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
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Delete a project.
     */
    public function delete(Project $project, ProjectService $service): void
    {
        $service->delete($project);

        session()->flash('status', 'Project deleted successfully.');
    }

    protected function bulkCollectionProperty(): string
    {
        return 'projects';
    }

    public function deleteSelected(ProjectService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($project = Project::find($id)) {
                $service->delete($project);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' project(s) deleted.');
    }

    public function render()
    {
        return view('livewire.projects.index');
    }
}
