<?php

namespace App\Livewire\MasterItems;

use App\Exceptions\ItemInUseException;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Services\MasterItemService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public MasterType $masterType;

    public string $search = '';

    public function mount(MasterType $masterType): void
    {
        $masterType->load('fields');

        if ($masterType->kode === null) {
            $masterType->kode = 'MANUAL-'.$masterType->id;
        }

        $this->masterType = $masterType;
    }

    /**
     * Minimal guard: an item flagged as AR-only must not slip into an AP
     * dropdown, and vice versa.
     */
    public function canBePickedAs(?string $side): bool
    {
        if ($side === 'ar') {
            return $this->flag_ar === null || $this->flag_ar === true;
        }

        if ($side === 'ap') {
            return $this->flag_ap === null || $this->flag_ap === true;
        }

        return true;
    }

    /**
     * Delete a master item.
     */
    public function delete(MasterItem $item, MasterItemService $service): void
    {
        $service->delete($item);

        session()->flash('status', 'Master item deleted.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of master items for the selected type.
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        return app(MasterItemService::class)->paginate($this->masterType, $this->search, $this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'items';
    }

    public function deleteSelected(MasterItemService $service): void
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($this->selectedIds as $id) {
            if ($item = MasterItem::find($id)) {
                try {
                    $service->delete($item);
                    $deleted++;
                } catch (ItemInUseException) {
                    $skipped++;
                }
            }
        }

        $this->selectedIds = [];

        $message = $deleted.' master item(s) deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' item(s) skipped (still in use by transactions).';
        }

        session()->flash('status', $message);
    }

    public function render()
    {
        return view('livewire.master-items.index');
    }
}
