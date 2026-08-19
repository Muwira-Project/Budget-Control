<?php

namespace App\Livewire\Receivables;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Project;
use App\Models\Receivable;
use App\Services\ReceivableService;
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

    public string $agingFilter = '';

    public ?int $holdingId = null;

    public string $holdReason = '';

    /**
     * Delete a receivable.
     */
    public function delete(Receivable $receivable, ReceivableService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can delete receivables.');

            return;
        }

        $service->delete($receivable);

        session()->flash('status', 'Receivable deleted successfully.');
    }

    /**
     * Open the hold modal for a receivable.
     */
    public function hold(int $receivableId): void
    {
        $this->holdingId = $receivableId;
        $this->holdReason = '';
    }

    /**
     * Confirm the hold with a reason.
     */
    public function confirmHold(ReceivableService $service): void
    {
        if ($this->holdingId === null) {
            return;
        }

        if (trim($this->holdReason) === '') {
            session()->flash('error', 'Hold reason is required.');

            return;
        }

        /** @var Receivable|null $receivable */
        $receivable = Receivable::find($this->holdingId);

        if ($receivable === null) {
            $this->reset('holdingId', 'holdReason');

            return;
        }

        $service->hold($receivable, trim($this->holdReason));

        session()->flash('status', 'Receivable is on hold.');

        $this->reset('holdingId', 'holdReason');
    }

    /**
     * Release a held receivable.
     */
    public function release(Receivable $receivable, ReceivableService $service): void
    {
        if (! Gate::allows('releaseReceivables', $receivable)) {
            session()->flash('error', 'Only admins can release receivables.');

            return;
        }

        $service->release($receivable);

        session()->flash('status', 'Receivable released.');
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
     * Reset the pagination when the aging filter changes.
     */
    public function updatedAgingFilter(): void
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

        return app(ReceivableService::class)->paginate($project, $this->statusFilter !== '' ? $this->statusFilter : null, $this->agingFilter !== '' ? $this->agingFilter : null, $this->perPage);
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
     * The aging buckets available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function agingBuckets(): array
    {
        return [
            'current' => 'Current (not due)',
            '1_30' => '1-30 days overdue',
            '31_60' => '31-60 days overdue',
            '61_90' => '61-90 days overdue',
            'over_90' => 'Over 90 days',
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
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can delete receivables.');

            return;
        }

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
