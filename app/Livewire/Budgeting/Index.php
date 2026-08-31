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
     * Get the base allocation query with eager loading and filters.
     */
    protected function getBaseAllocationQuery()
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;
        $isAdmin = auth()->user()->isAdmin();

        return ProjectAkun::query()
            ->with(['project', 'akun', 'pihakItem'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()));
    }

    /**
     * Get the base plan items query with eager loading and filters.
     */
    protected function getBasePlanItemsQuery()
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        return BudgetPlanItem::query()
            ->with(['budgetPlan.project', 'akun'])
            ->when($project, fn ($query) => $query->whereHas('budgetPlan', fn ($q) => $q->where('project_id', $project->id)));
    }

    /**
     * Build the merged rows array from allocations and plan items.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildMergedRows(): array
    {
        $allocations = $this->getBaseAllocationQuery()->get();
        $planItems = $this->getBasePlanItemsQuery()->get();

        $byKey = [];

        foreach ($allocations as $allocation) {
            $key = ($allocation->project_id ?? 'non-project').'-'.$allocation->akun_id;
            // For non-project, include type and party/custom_name in key to allow multiple rows per akun
            if ($allocation->project_id === null) {
                $key .= '-'.$allocation->type;
                if ($allocation->pihak_item_id) {
                    $key .= '-'.$allocation->pihak_item_id;
                } elseif ($allocation->custom_name) {
                    $key .= '-'.md5($allocation->custom_name);
                }
            }

            // Check if this allocation was created from a budget plan
            $budgetingNumber = null;
            if ($allocation->project_id !== null) {
                $planItem = BudgetPlanItem::whereHas('budgetPlan', function ($q) use ($allocation) {
                    $q->where('project_id', $allocation->project_id);
                })->where('akun_id', $allocation->akun_id)->first();
                if ($planItem) {
                    $budgetingNumber = $planItem->budgetPlan->nomor;
                }
            }

            $byKey[$key] = [
                'project' => $allocation->project,
                'is_non_project' => $allocation->project_id === null,
                'akun' => $allocation->akun,
                'budget' => (float) $allocation->budget,
                'allocation' => $allocation,
                'plan' => null,
                'budgeting_number' => $budgetingNumber,
            ];
        }

        foreach ($planItems as $item) {
            $key = ($item->budgetPlan->project_id ?? 'non-project').'-'.$item->akun_id;

            if (isset($byKey[$key])) {
                // Keep the allocation's own budget; only fill the plan reference.
                $byKey[$key]['plan'] = $item;
                $byKey[$key]['budgeting_number'] = $item->budgetPlan->nomor;
            } else {
                $byKey[$key] = [
                    'project' => $item->budgetPlan->project,
                    'is_non_project' => $item->budgetPlan->project_id === null,
                    'akun' => $item->akun,
                    'budget' => (float) $item->nominal,
                    'allocation' => null,
                    'plan' => $item,
                    'budgeting_number' => $item->budgetPlan->nomor,
                ];
            }
        }

        return collect($byKey)->values()->all();
    }

    /**
     * Apply search filter to rows.
     */
    protected function applySearchFilter(array $rows): array
    {
        if ($this->search === '') {
            return $rows;
        }

        $needle = strtolower($this->search);

        return array_filter($rows, function (array $row) use ($needle) {
            $project = $row['project'];
            $akun = $row['akun'];
            $haystack = strtolower(
                trim(($project?->kode ?? ($row['is_non_project'] ? 'non-project' : '')).' '.($project?->nama ?? ($row['is_non_project'] ? 'Non-Project' : '')).' '.($akun?->kode_akun ?? '').' '.($akun?->nama_akun ?? ''))
            );

            return str_contains($haystack, $needle);
        });
    }

    /**
     * Sort rows by project code then account code (non-project first).
     */
    protected function sortRows(array $rows): array
    {
        usort($rows, function (array $a, array $b) {
            // Non-project first
            if ($a['is_non_project'] !== $b['is_non_project']) {
                return $a['is_non_project'] ? -1 : 1;
            }

            // Then by project code
            $projectCodeA = strtolower($a['project']?->kode ?? '');
            $projectCodeB = strtolower($b['project']?->kode ?? '');
            if ($projectCodeA !== $projectCodeB) {
                return strcmp($projectCodeA, $projectCodeB);
            }

            // Then by account code
            return strcmp(
                strtolower($a['akun']?->kode_akun ?? ''),
                strtolower($b['akun']?->kode_akun ?? '')
            );
        });

        return array_values($rows);
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
        // Build merged rows once and cache
        if ($this->cachedRows === []) {
            $this->cachedRows = $this->buildMergedRows();
        }

        $rows = $this->applySearchFilter($this->cachedRows);
        $rows = $this->sortRows($rows);

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
        // Return cached summary if available
        if ($this->cachedSummary !== null) {
            return $this->cachedSummary;
        }

        $project = $this->projectId ? Project::find($this->projectId) : null;
        $isAdmin = auth()->user()->isAdmin();

        // For summary, we need ALL allocations (unfiltered by status/search)
        // to match the header cards behavior
        $allAllocations = ProjectAkun::query()
            ->with(['project', 'akun', 'pihakItem'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
            ->get();

        $allPlanItems = BudgetPlanItem::query()
            ->when($project, fn ($query) => $query->whereHas('budgetPlan', fn ($q) => $q->where('project_id', $project->id)))
            ->get();

        // Realisasi yang berasal dari cashflow non-project (sumber pengeluaran_lain,
        // project_id null) — dihitung ke total realisasi non-project.
        $nonProjectRealisasi = $project === null
            ? (float) Realisasi::query()->whereNull('project_id')->sum('nominal')
            : 0.0;

        $this->cachedSummary = [
            'total_budget' => (float) $allPlanItems->sum('nominal')
                + (float) ProjectAkun::query()
                    ->whereNull('project_id')
                    ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
                    ->sum('budget'),
            'total_allocation' => (float) $allAllocations->sum('allocation')
                + ($project === null
                    ? (float) ProjectAkun::query()
                        ->whereNull('project_id')
                        ->when(! $isAdmin, fn ($query) => $query->where('created_by', auth()->id()))
                        ->sum('allocation')
                    : 0.0),
            'total_realisasi' => (float) $allAllocations->sum('total_realisasi') + $nonProjectRealisasi,
        ];

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
