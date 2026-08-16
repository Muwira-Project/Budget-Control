<?php

namespace App\Livewire\Mandors;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Mandor;
use App\Models\Payable;
use App\Models\Realisasi;
use App\Services\MandorService;
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

    protected function isUsed(Mandor $mandor): bool
    {
        return Realisasi::where('mandor_id', $mandor->id)->exists()
            || Payable::where('mandor_id', $mandor->id)->exists();
    }

    public function delete(Mandor $mandor, MandorService $service): void
    {
        if ($this->isUsed($mandor)) {
            session()->flash('error', 'Mandor is still used in actuals or payables and cannot be deleted.');

            return;
        }

        $service->delete($mandor);

        session()->flash('status', 'Mandor deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function mandors(): LengthAwarePaginator
    {
        return app(MandorService::class)->paginate($this->search, $this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'mandors';
    }

    public function deleteSelected(MandorService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $mandor = Mandor::find($id)) {
                continue;
            }
            if ($this->isUsed($mandor)) {
                $skipped++;

                continue;
            }
            $service->delete($mandor);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' mandor(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' mandor(s) are still in use and cannot be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.mandors.index');
    }
}
