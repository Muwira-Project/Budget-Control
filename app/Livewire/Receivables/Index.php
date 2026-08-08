<?php

namespace App\Livewire\Receivables;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Project;
use App\Models\Receivable;
use App\Services\ReceivableService;
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
     * Delete a receivable.
     */
    public function delete(Receivable $receivable, ReceivableService $service): void
    {
        $service->delete($receivable);

        session()->flash('status', 'Receivable deleted successfully.');
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
     * The paginated list of receivables.
     */
    #[Computed]
    public function receivables(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        return app(ReceivableService::class)->paginate($project, $this->statusFilter !== '' ? $this->statusFilter : null, $this->perPage);
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
     * The receivable statuses available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            'belum_dibayar' => 'Unpaid',
            'sebagian' => 'Partial',
            'lunas' => 'Paid',
        ];
    }

    /**
     * Render the receivable index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'receivables';
    }

    public function deleteSelected(ReceivableService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($receivable = Receivable::find($id)) {
                $service->delete($receivable);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' receivable(s) deleted.');
    }

    public function render()
    {
        return view('livewire.receivables.index');
    }
}
