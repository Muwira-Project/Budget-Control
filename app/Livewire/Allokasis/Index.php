<?php

namespace App\Livewire\Allokasis;

use App\Enums\AllocationStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Services\ProjectAkunService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public ?int $projectId = null;

    public string $statusFilter = '';

    /**
     * Delete an allocation request.
     */
    public function delete(ProjectAkun $allocation, ProjectAkunService $service): void
    {
        if (! Gate::allows('manageDraft', $allocation)) {
            session()->flash('error', 'Staff can only manage their own draft allocations.');

            return;
        }

        if ($allocation->isApproved()) {
            session()->flash('error', 'Approved allocations cannot be deleted.');

            return;
        }

        $service->delete($allocation);

        session()->flash('status', 'Allocation deleted successfully.');
    }

    /**
     * Submit a draft allocation for approval.
     */
    public function submit(ProjectAkun $allocation, ProjectAkunService $service): void
    {
        if (! Gate::allows('manageDraft', $allocation)) {
            session()->flash('error', 'Staff can only submit their own draft allocations.');

            return;
        }

        if ($allocation->status !== AllocationStatus::Draft) {
            session()->flash('error', 'Only allocations with draft status can be submitted.');

            return;
        }

        $service->submit($allocation);

        session()->flash('status', 'Allocation submitted for approval.');
    }

    /**
     * Approve a waiting allocation (admin only).
     */
    public function approve(ProjectAkun $allocation, ProjectAkunService $service): void
    {
        if (! Gate::allows('approve', $allocation)) {
            session()->flash('error', 'Only admins can approve allocations.');

            return;
        }

        if ($allocation->status !== AllocationStatus::Waiting) {
            session()->flash('error', 'Only allocations awaiting approval can be approved.');

            return;
        }

        $service->approve($allocation);

        session()->flash('status', 'Allocation approved.');
    }

    /**
     * Reject a waiting allocation (admin only).
     */
    public function reject(ProjectAkun $allocation, ProjectAkunService $service): void
    {
        if (! Gate::allows('approve', $allocation)) {
            session()->flash('error', 'Only admins can reject allocations.');

            return;
        }

        if ($allocation->status !== AllocationStatus::Waiting) {
            session()->flash('error', 'Only allocations awaiting approval can be rejected.');

            return;
        }

        $service->reject($allocation);

        session()->flash('status', 'Allocation rejected.');
    }

    /**
     * Reset the pagination when the project filter changes.
     */
    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the status filter changes.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of allocations.
     */
    #[Computed]
    public function allocations(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        if (! Gate::allows('viewAny', ProjectAkun::class)) {
            return ProjectAkun::query()
                ->with(['project', 'akun'])
                ->where('created_by', auth()->id())
                ->where('status', AllocationStatus::Draft)
                ->orderBy('project_id')
                ->orderBy('akun_id')
                ->paginate($this->perPage)
                ->withQueryString();
        }

        return app(ProjectAkunService::class)->paginate($project, $this->statusFilter !== '' ? $this->statusFilter : null, $this->perPage);
    }

    /**
     * The projects available for filtering.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * The allocation statuses available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            'draft' => AllocationStatus::Draft->label(),
            'waiting' => AllocationStatus::Waiting->label(),
            'approved' => AllocationStatus::Approved->label(),
            'rejected' => AllocationStatus::Rejected->label(),
        ];
    }

    /**
     * Render the allocation index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'allocations';
    }

    public function deleteSelected(ProjectAkunService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $allocation = ProjectAkun::find($id)) {
                continue;
            }
            if (! Gate::allows('manageDraft', $allocation)) {
                $skipped++;

                continue;
            }
            $service->delete($allocation);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' allocation(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' allocation(s) cannot be deleted in their current state.');
        }
    }

    public function render()
    {
        return view('livewire.allokasis.index');
    }
}
