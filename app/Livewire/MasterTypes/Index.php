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
     * Delete a master type (items cascade).
     */
    public function delete(MasterType $type, MasterTypeService $service): void
    {
        $service->delete($type);

        session()->flash('status', 'Master type deleted.');
    }

    /**
     * The paginated list of master types.
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
                $service->delete($type);
                $deleted++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' master type(s) deleted.');
    }

    public function render()
    {
        return view('livewire.master-types.index');
    }
}