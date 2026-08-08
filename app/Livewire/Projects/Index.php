<?php

namespace App\Livewire\Projects;

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

    public ?int $selectedProjectId = null;

    /**
     * Delete a project.
     */
    public function delete(Project $project, ProjectService $service): void
    {
        $service->delete($project);

        session()->flash('status', 'Project deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of projects.
     */
    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return Project::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            }))
            ->latest()
            ->paginate($this->perPage);
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
            ? Project::withSum('projectAkuns as budget_total', 'budget')->find($this->selectedProjectId)
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
            ->with(['akun', 'kategori', 'vendor', 'supplier', 'mandor', 'investor'])
            ->where('project_id', $this->selectedProjectId)
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Render the project index page.
     */
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