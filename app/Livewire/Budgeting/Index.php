<?php

namespace App\Livewire\Budgeting;

use App\Enums\AllocationStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Services\BudgetPlanService;
use App\Services\BudgetingRowsService;
use App\Services\BudgetingSummaryService;
use App\Services\ProjectAkunService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public mixed $projectId = null;

    public string $statusFilter = '';

    public string $search = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

    /** Cached merged rows for summary computation */
    protected array $cachedRows = [];

    /** Cached summary data */
    protected ?array $cachedSummary = null;

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
        $this->cachedRows = [];
        $this->cachedSummary = null;

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
        $this->cachedRows = [];
        $this->cachedSummary = null;

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
        $this->cachedRows = [];
        $this->cachedSummary = null;

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
        $this->cachedRows = [];
        $this->cachedSummary = null;

        session()->flash('status', 'Allocation rejected.');
    }

    /**
     * Bulk delete selected allocations (skip approved).
     */
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
        $this->cachedRows = [];
        $this->cachedSummary = null;
        session()->flash('status', $deleted.' allocation(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' allocation(s) cannot be deleted in their current state.');
        }
    }

    /**
     * Generate draft allocations from a budget plan (admin only).
     */
    public function createAllocations(BudgetPlan $budgetPlan, BudgetPlanService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can create allocations from a Budget Plan.');

            return;
        }

        $budgetPlan->load('items');

        if ($budgetPlan->items->isEmpty()) {
            session()->flash('error', 'This budget plan has no account details yet.');

            return;
        }

        $created = $service->createAllocationsFromPlan($budgetPlan);
        $this->cachedRows = [];
        $this->cachedSummary = null;

        session()->flash('status', $created > 0
            ? $created.' allocation draft(s) created from the Budget. Submit them under Budgeting for admin approval.'
            : 'All accounts in this budget plan have already been allocated to the project.');
    }

    /**
     * Create a Non-Project Allocation draft (admin only).
     */
    public function createNonProjectAllocation(ProjectAkunService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can create non-project allocations.');

            return;
        }

        // Redirect to create page with project_id = null (non-project)
        $this->redirectRoute('allokasis.create', ['project_id' => 'non-project'], navigate: true);
    }

    /**
     * Reset the pagination when the project filter changes.
     */
    public function updatedProjectId(): void
    {
        $this->cachedRows = [];
        $this->cachedSummary = null;
        $this->resetPage();
    }

    /**
     * Reset the pagination when the status filter changes.
     */
    public function updatedStatusFilter(): void
    {
        $this->cachedRows = [];
        $this->cachedSummary = null;
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
     * The projects available for filtering.
     */
    #[Computed]
    public function projects(): Collection
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
     * Required by BulkSelection trait.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'rows';
    }

    /** Get the base allocation query with eager loading and filters. */
    protected function getBaseAllocationQuery()
    {
        $isAdmin = auth()->user()->isAdmin();
        $isNonProjectFilter = ($this->projectId === 'non-project' || $this->projectId === 'non_project');
        $project = (! $isNonProjectFilter && $this->projectId) ? Project::find($this->projectId) : null;

        return ProjectAkun::query()
            ->with(['project', 'akun', 'pihakItem'])
            ->when($isNonProjectFilter, fn ($query) => $query->whereNull('project_id'))
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()));
    }

    /** Get the base plan items query with eager loading and filters. */
    protected function getBasePlanItemsQuery()
    {
        $isNonProjectFilter = ($this->projectId === 'non-project' || $this->projectId === 'non_project');
        if ($isNonProjectFilter) {
            return BudgetPlanItem::query()->whereRaw('1 = 0');
        }

        $project = $this->projectId ? Project::find($this->projectId) : null;

        return BudgetPlanItem::query()
            ->with(['budgetPlan.project', 'akun'])
            ->when($project, fn ($query) => $query->whereHas('budgetPlan', fn ($subquery) => $subquery->where('project_id', $project->id)));
    }

    /**
     * Combine budget-plan items and allocations into one view.
     *
     * Every account that has either a planned budget or an allocation appears
     * exactly once. When both exist they are merged on (project, akun); the
     * row carries the plan nominal (Budget) and the allocation state.
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        if ($this->cachedRows === []) {
            $allocations = $this->getBaseAllocationQuery()->get();
            $planItems = $this->getBasePlanItemsQuery()->get();
            $this->cachedRows = app(BudgetingRowsService::class)->buildMergedRows($allocations, $planItems);
        }

        $rowsService = app(BudgetingRowsService::class);
        $rows = $rowsService->applySearchFilter($this->cachedRows, $this->search);
        $rows = $rowsService->sortRows($rows);

        $perPage = $this->perPage;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($slice, count($rows), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Summary totals for the header cards (unfiltered by status/search).
     */
    #[Computed]
    public function summary(): array
    {
        if ($this->cachedSummary !== null) {
            return $this->cachedSummary;
        }

        $this->cachedSummary = app(BudgetingSummaryService::class)->calculate(
            $this->projectId,
            auth()->user()->isAdmin(),
            (int) auth()->id(),
        );

        return $this->cachedSummary;
    }
    /**
     * Render the combined Budgeting page.
     */
    public function render()
    {
        return view('livewire.budgeting.index');
    }
}
