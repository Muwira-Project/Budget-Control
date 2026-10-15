<?php

namespace App\Livewire\FundTransfers;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\FundTransfer;
use App\Services\FundTransferService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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

    /** Post an imported draft after review. */
    public function postDraft(int $transferId, FundTransferService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can post imported fund transfers.');

            return;
        }

        $transfer = FundTransfer::find($transferId);
        if ($transfer === null || ! $transfer->status->isDraft()) {
            session()->flash('error', 'This fund transfer is no longer an unposted draft.');

            return;
        }

        $service->post($transfer);
        session()->flash('status', 'Fund transfer posted successfully.');
    }

    /** Post all selected fund transfer drafts after admin review. */
    public function postSelected(FundTransferService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can post imported fund transfers.');

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $this->selectedIds)));
        $posted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $transfer = FundTransfer::query()->whereKey($id)->where('status', 'draft')->first();
            if ($transfer === null) {
                $skipped++;

                continue;
            }

            try {
                DB::transaction(fn () => $service->post($transfer));
                $posted++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        $this->selectedIds = [];
        session()->flash('status', $posted.' fund transfer(s) posted.'.($skipped > 0 ? ' '.$skipped.' non-draft or unavailable row(s) skipped.' : ''));
    }

    /**
     * The paginated list of fund transfers.
     */
    #[Computed]
    public function transfers(): LengthAwarePaginator
    {
        return app(FundTransferService::class)->paginate($this->perPage);
    }

    /** Number of selected drafts available for posting. */
    #[Computed]
    public function postableSelectedCount(): int
    {
        if (! auth()->user()->isAdmin() || $this->selectedIds === []) {
            return 0;
        }

        return FundTransfer::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $this->selectedIds))))
            ->where('status', 'draft')
            ->count();
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
