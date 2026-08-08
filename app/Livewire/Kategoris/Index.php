<?php

namespace App\Livewire\Kategoris;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Kategori;
use App\Services\KategoriService;
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

    /**
     * Delete a kategori.
     */
    public function delete(Kategori $kategori, KategoriService $service): void
    {
        $service->delete($kategori);

        session()->flash('status', 'Category deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of kategoris.
     */
    #[Computed]
    public function kategoris(): LengthAwarePaginator
    {
        return app(KategoriService::class)->paginate($this->search, $this->perPage);
    }

    /**
     * Render the kategori index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'kategoris';
    }

    public function deleteSelected(KategoriService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($kategori = Kategori::find($id)) {
                $service->delete($kategori);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' category(ies) deleted.');
    }

    public function render()
    {
        return view('livewire.kategoris.index');
    }
}
