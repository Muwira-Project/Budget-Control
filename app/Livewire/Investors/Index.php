<?php

namespace App\Livewire\Investors;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Investor;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Realisasi;
use App\Services\InvestorService;
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

    protected function isUsed(Investor $investor): bool
    {
        return Realisasi::where('investor_id', $investor->id)->exists()
            || Payable::where('investor_id', $investor->id)->exists()
            || PaymentRequest::where('investor_id', $investor->id)->exists();
    }

    public function delete(Investor $investor, InvestorService $service): void
    {
        if ($this->isUsed($investor)) {
            session()->flash('error', 'Investor is still used in actuals, payables, or payment requests and cannot be deleted.');

            return;
        }

        $service->delete($investor);

        session()->flash('status', 'Investor deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function investors(): LengthAwarePaginator
    {
        return app(InvestorService::class)->paginate($this->search, $this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'investors';
    }

    public function deleteSelected(InvestorService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $investor = Investor::find($id)) {
                continue;
            }
            if ($this->isUsed($investor)) {
                $skipped++;

                continue;
            }
            $service->delete($investor);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' investor(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' investor(s) are still in use and cannot be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.investors.index');
    }
}
