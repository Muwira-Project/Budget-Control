<?php

namespace App\Livewire\FundTransfers;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\FundTransfer;
use App\Services\FundTransferService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    /**
     * Delete a fund transfer.
     */
    public function delete(FundTransfer $transfer): void
    {
        $transfer->delete();

        session()->flash('status', 'Fund transfer deleted.');
    }

    /**
     * The paginated list of fund transfers.
     */
    #[Computed]
    public function transfers(): LengthAwarePaginator
    {
        return app(FundTransferService::class)->paginate($this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'transfers';
    }

    public function deleteSelected(): void
    {
        FundTransfer::whereIn('id', $this->selectedIds)->delete();
        $count = count($this->selectedIds);
        $this->selectedIds = [];
        session()->flash('status', $count.' fund transfer(s) deleted.');
    }

    public function render()
    {
        return view('livewire.fund-transfers.index');
    }
}
