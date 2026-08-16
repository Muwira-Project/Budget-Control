<?php

namespace App\Livewire\MasterItems;

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
        foreach ($this->selectedIds as $id) {
            if ($item = MasterItem::find($id)) {
                $service->delete($item);
                $deleted++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' master item(s) deleted.');
    }

    public function render()
    {
        return view('livewire.master-items.index');
    }
}
