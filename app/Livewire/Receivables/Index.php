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

    public string $arCategoryFilter = '';

    public string $poNumberFilter = '';

    public ?string $dateFromFilter = null;

    public ?string $dateToFilter = null;

    public ?float $amountMinFilter = null;

    public ?float $amountMaxFilter = null;

    public ?int $holdingId = null;

    public string $holdReason = '';

    public bool $summaryPositionBottom = true;

    /** Reset pagination when filters change. */
    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAgingFilter(): void
    {
        $this->resetPage();
    }

    public function updatedArCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPoNumberFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFromFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateToFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAmountMinFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAmountMaxFilter(): void
    {
        $this->resetPage();
    }

    /** Delete a receivable. */
    public function delete(Receivable $receivable, ReceivableService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can delete receivables.');

            return;
        }

        $service->delete($receivable);

        session()->flash('status', 'Receivable deleted successfully.');
    }

    /** Open the hold modal for a receivable. */
    public function hold(int $receivableId): void
    {
        $this->holdingId = $receivableId;
        $this->holdReason = '';
    }

    /** Confirm the hold with a reason. */
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

    /** Release a held receivable. */
    public function release(Receivable $receivable, ReceivableService $service): void
    {
        if (! Gate::allows('releaseReceivables', $receivable)) {
            session()->flash('error', 'Only admins can release receivables.');

            return;
        }

        $service->release($receivable);

        session()->flash('status', 'Receivable released.');
    }

    /** The paginated list of receivables. */
    #[Computed]
    public function receivables(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        return app(ReceivableService::class)->paginate(
            $project,
            $this->statusFilter !== '' ? $this->statusFilter : null,
            $this->agingFilter !== '' ? $this->agingFilter : null,
            $this->arCategoryFilter !== '' ? $this->arCategoryFilter : null,
            $this->poNumberFilter !== '' ? $this->poNumberFilter : null,
            $this->dateFromFilter,
            $this->dateToFilter,
            $this->amountMinFilter,
            $this->amountMaxFilter,
            $this->perPage
        );
    }

    /** AR Summary grouped by category (billed, unbilled, inprogress) with grand total. */
    #[Computed]
    public function arSummary(): array
    {
        return app(ReceivableService::class)->getArSummary(
            $this->arCategoryFilter !== '' ? $this->arCategoryFilter : null,
            $this->poNumberFilter !== '' ? $this->poNumberFilter : null,
            $this->dateFromFilter,
            $this->dateToFilter,
            $this->amountMinFilter,
            $this->amountMaxFilter
        );
    }

    /** The projects available for filtering. */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /** The receivable statuses available for filtering. */
    #[Computed]
    public function statuses(): array
    {
        return [
            'belum_dibayar' => 'Unpaid',
            'sebagian' => 'Partial',
            'lunas' => 'Paid',
        ];
    }

    /** The AR categories available for filtering. */
    #[Computed]
    public function arCategories(): array
    {
        return [
            '' => 'All Categories',
            'billed' => 'Billed (Done + PO)',
            'unbilled' => 'Unbilled (Done, No PO)',
            'inprogress' => 'In Progress',
        ];
    }

    /** The aging buckets available for filtering. */
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

    /** Render the receivable index page. */
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