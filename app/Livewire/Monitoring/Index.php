<?php

namespace App\Livewire\Monitoring;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Services\MonitoringPeriodService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $search = '';

    public ?int $projectFilter = null;

    public function delete(MonitoringPeriod $period, MonitoringPeriodService $service): void
    {
        $service->delete($period);

        session()->flash('status', 'Monitoring period deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function periods(): LengthAwarePaginator
    {
        return app(MonitoringPeriodService::class)->paginate($this->projectFilter, $this->search, $this->perPage);
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * Budget, actual, and variance totals keyed by period id (one pass per render).
     *
     * @return array<int, array{budget: float, actual: float, variance: float}>
     */
    #[Computed]
    public function totalsByPeriod(): array
    {
        $service = app(MonitoringPeriodService::class);
        $totals = [];

        foreach ($this->periods as $period) {
            $budget = $service->budgetTotal($period);
            $actual = $service->actualTotal($period);
            $totals[$period->id] = [
                'budget' => $budget,
                'actual' => $actual,
                'variance' => $budget - $actual,
            ];
        }

        return $totals;
    }

    protected function bulkCollectionProperty(): string
    {
        return 'periods';
    }

    public function deleteSelected(MonitoringPeriodService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($period = MonitoringPeriod::find($id)) {
                $service->delete($period);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' monitoring period(s) deleted.');
    }

    public function render()
    {
        return view('livewire.monitoring.index');
    }
}
