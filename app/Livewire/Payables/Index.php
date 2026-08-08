<?php

namespace App\Livewire\Payables;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Payable;
use App\Models\Project;
use App\Services\PayableService;
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

    public string $statusFilter = '';

    /**
     * Delete a payable.
     */
    public function delete(Payable $payable, PayableService $service): void
    {
        $service->delete($payable);

        session()->flash('status', 'Payable deleted successfully.');
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
     * The paginated list of payables.
     */
    #[Computed]
    public function payables(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        return app(PayableService::class)->paginate($project, $this->statusFilter !== '' ? $this->statusFilter : null, $this->perPage);
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
     * The payable statuses available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            'belum_bayar' => 'Unpaid',
            'sebagian' => 'Partial',
            'lunas' => 'Paid',
        ];
    }

    /**
     * Render the payable index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'payables';
    }

    public function deleteSelected(PayableService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($payable = Payable::find($id)) {
                $service->delete($payable);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' payable(s) deleted.');
    }

    public function render()
    {
        return view('livewire.payables.index');
    }
}
