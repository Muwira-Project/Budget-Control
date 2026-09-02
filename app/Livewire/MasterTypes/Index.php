<?php

namespace App\Livewire\MasterTypes;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\MasterType;
use App\Services\MasterTypeService;
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
     * Delete a master menu (items and fields cascade).
     */
    public function delete(MasterType $type, MasterTypeService $service): void
    {
        if ($type->is_system) {
            session()->flash('error', 'System master menus cannot be deleted.');

            return;
        }

        $service->delete($type);

        session()->flash('status', 'Master menu deleted.');
    }

    /**
     * The paginated list of master menus.
     */
    #[Computed]
    public function types(): LengthAwarePaginator
    {
        return app(MasterTypeService::class)->paginate($this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'types';
    }

    public function deleteSelected(MasterTypeService $service): void
    {
        $deleted = 0;
        foreach ($this->selectedIds as $id) {
            if ($type = MasterType::find($id)) {
                if ($type->is_system) {
                    continue;
                }
                $service->delete($type);
                $deleted++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' master menu(s) deleted.');
    }

    public function render()
    {
        return view('livewire.master-types.index');
    }
}
