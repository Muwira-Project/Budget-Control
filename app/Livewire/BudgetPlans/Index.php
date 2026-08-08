<?php

namespace App\Livewire\BudgetPlans;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\BudgetPlan;
use App\Models\Project;
use App\Services\BudgetPlanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public ?int $projectId = null;

    public string $search = '';

    /**
     * Delete a budget plan.
     */
    public function delete(BudgetPlan $budgetPlan, BudgetPlanService $service): void
    {
        $service->delete($budgetPlan);

        session()->flash('status', 'Budget plan deleted successfully.');
    }

    /**
     * Generate draft allocations from the plan items.
     */
    public function createAllocations(BudgetPlan $budgetPlan, BudgetPlanService $service): void
    {
        $budgetPlan->load('items');

        if ($budgetPlan->items->isEmpty()) {
            session()->flash('error', 'This budget plan has no account details yet.');

            return;
        }

        $created = $service->createAllocationsFromPlan($budgetPlan);

        session()->flash('status', $created > 0
            ? $created.' allocation draft(s) created from the Budget. Submit them under Budget Allocation for admin approval.'
            : 'All accounts in this budget plan have already been allocated to the project.');
    }

    /**
     * Reset the pagination when the project filter changes.
     */
    public function updatedProjectId(): void
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
     * The paginated list of budget plans.
     */
    #[Computed]
    public function budgetPlans(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        return app(BudgetPlanService::class)->paginate($project, $this->search, $this->perPage);
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
     * Render the budget plan index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'budgetPlans';
    }

    public function deleteSelected(BudgetPlanService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($plan = BudgetPlan::find($id)) {
                $service->delete($plan);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' budget plan(s) deleted.');
    }

    public function render()
    {
        return view('livewire.budget-plans.index');
    }
}
