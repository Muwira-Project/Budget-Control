<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Services\SupplierService;
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
     * Delete a supplier.
     */
    public function delete(Supplier $supplier, SupplierService $service): void
    {
        $used = Realisasi::where('supplier_id', $supplier->id)->exists()
            || Payable::where('supplier_id', $supplier->id)->exists()
            || PaymentRequest::where('supplier_id', $supplier->id)->exists();

        if ($used) {
            session()->flash('error', 'Supplier is still used in actuals, payables, or payment requests and cannot be deleted.');

            return;
        }

        $service->delete($supplier);

        session()->flash('status', 'Supplier deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of suppliers.
     */
    #[Computed]
    public function suppliers(): LengthAwarePaginator
    {
        return app(SupplierService::class)->paginate($this->search, $this->perPage);
    }

    /**
     * Render the supplier index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'suppliers';
    }

    public function deleteSelected(SupplierService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $supplier = Supplier::find($id)) {
                continue;
            }
            $used = Realisasi::where('supplier_id', $supplier->id)->exists()
                || Payable::where('supplier_id', $supplier->id)->exists()
                || PaymentRequest::where('supplier_id', $supplier->id)->exists();
            if ($used) {
                $skipped++;

                continue;
            }
            $service->delete($supplier);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' supplier(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' supplier(s) are still in use and cannot be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.suppliers.index');
    }
}
