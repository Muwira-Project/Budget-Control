<?php

namespace App\Livewire\Budgeting;

use App\Enums\AllocationStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Services\BudgetPlanService;
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

    public ?int $projectId = null;

    public string $statusFilter = '';

    public string $search = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

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
        $project = $this->projectId ? Project::find($this->projectId) : null;
        $isAdmin = auth()->user()->isAdmin();

        $allocations = ProjectAkun::query()
            ->with(['project', 'akun'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
            ->get();

        $planItems = BudgetPlanItem::query()
            ->with(['budgetPlan.project', 'akun'])
            ->when($project, fn ($query) => $query->whereHas('budgetPlan', fn ($q) => $q->where('project_id', $project->id)))
            ->get();

        // Merge allocations and plan items on (project_id, akun_id).
        $byKey = [];

        foreach ($allocations as $allocation) {
            $key = ($allocation->project_id ?? 'non-project').'-'.$allocation->akun_id;
            $byKey[$key] = [
                'project' => $allocation->project,
                'is_non_project' => $allocation->project_id === null,
                'akun' => $allocation->akun,
                'budget' => (float) $allocation->budget,
                'allocation' => $allocation,
                'plan' => null,
            ];
        }

        foreach ($planItems as $item) {
            $key = ($item->budgetPlan->project_id ?? 'non-project').'-'.$item->akun_id;

            if (isset($byKey[$key])) {
                // Keep the allocation's own budget; only fill the plan reference.
                $byKey[$key]['plan'] = $item;
            } else {
                $byKey[$key] = [
                    'project' => $item->budgetPlan->project,
                    'is_non_project' => $item->budgetPlan->project_id === null,
                    'akun' => $item->akun,
                    'budget' => (float) $item->nominal,
                    'allocation' => null,
                    'plan' => $item,
                ];
            }
        }

        $rows = collect($byKey)->values();

        // Search across project code/name and account code/name.
        if ($this->search !== '') {
            $needle = strtolower($this->search);
            $rows = $rows->filter(function (array $row) use ($needle) {
                $project = $row['project'];
                $akun = $row['akun'];
                $haystack = strtolower(
                    trim(($project?->kode ?? ($row['is_non_project'] ? 'non-project' : '')).' '.($project?->nama ?? ($row['is_non_project'] ? 'Non-Project' : '')).' '.($akun?->kode_akun ?? '').' '.($akun?->nama_akun ?? ''))
                );

                return str_contains($haystack, $needle);
            });
        }

        // Sort by project code then account code (non-project first).
        $rows = $rows->sortBy([
            fn ($row) => $row['is_non_project'] ? 0 : 1,
            fn ($row) => strtolower($row['project']?->kode ?? ''),
            fn ($row) => strtolower($row['akun']?->kode_akun ?? ''),
        ])->values();

        $perPage = $this->perPage;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $rows->slice(($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($slice, $rows->count(), $perPage, $page, [
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
        $project = $this->projectId ? Project::find($this->projectId) : null;
        $isAdmin = auth()->user()->isAdmin();

        $allocations = ProjectAkun::query()
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
            ->get();

        $planItems = BudgetPlanItem::query()
            ->when($project, fn ($query) => $query->whereHas('budgetPlan', fn ($q) => $q->where('project_id', $project->id)))
            ->get();

        // Realisasi yang berasal dari cashflow non-project (sumber pengeluaran_lain,
        // project_id null) — dihitung ke total realisasi non-project.
        $nonProjectRealisasi = $project === null
            ? (float) Realisasi::query()->whereNull('project_id')->sum('nominal')
            : 0.0;

        return [
            'total_budget' => (float) $planItems->sum('nominal')
                + (float) ProjectAkun::query()
                    ->whereNull('project_id')
                    ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
                    ->sum('budget'),
            'total_allocation' => (float) $allocations->sum('allocation')
                + ($project === null
                    ? (float) ProjectAkun::query()
                        ->whereNull('project_id')
                        ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
                        ->sum('allocation')
                    : 0.0),
            'total_realisasi' => (float) $allocations->sum('total_realisasi') + $nonProjectRealisasi,
        ];
    }

    /**
     * Render the combined Budgeting page.
     */
    public function render()
    {
        return view('livewire.budgeting.index');
    }
}
