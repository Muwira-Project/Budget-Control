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
    public function delete(FundTransfer $transfer, FundTransferService $service): void
    {
        $service->delete($transfer);
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

    public function deleteSelected(FundTransferService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if (! $transfer = FundTransfer::find($id)) {
                continue;
            }
            try {
                $service->delete($transfer);
                $count++;
            } catch (\Exception) {
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' fund transfer(s) deleted.');
    }

    public function render()
    {
        return view('livewire.fund-transfers.index');
    }
}
