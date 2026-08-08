<?php

namespace App\Livewire\Vendors;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Realisasi;
use App\Models\Vendor;
use App\Services\VendorService;
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
     * Delete a vendor.
     */
    public function delete(Vendor $vendor, VendorService $service): void
    {
        $used = Realisasi::where('vendor_id', $vendor->id)->exists()
            || Payable::where('vendor_id', $vendor->id)->exists()
            || PaymentRequest::where('vendor_id', $vendor->id)->exists();

        if ($used) {
            session()->flash('error', 'Vendor is still used in actuals, payables, or payment requests and cannot be deleted.');

            return;
        }

        $service->delete($vendor);

        session()->flash('status', 'Vendor deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of vendors.
     */
    #[Computed]
    public function vendors(): LengthAwarePaginator
    {
        return app(VendorService::class)->paginate($this->search, $this->perPage);
    }

    /**
     * Render the vendor index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'vendors';
    }

    public function deleteSelected(VendorService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $vendor = Vendor::find($id)) {
                continue;
            }
            $used = Realisasi::where('vendor_id', $vendor->id)->exists()
                || Payable::where('vendor_id', $vendor->id)->exists()
                || PaymentRequest::where('vendor_id', $vendor->id)->exists();
            if ($used) {
                $skipped++;

                continue;
            }
            $service->delete($vendor);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' vendor(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' vendor(s) are still in use and cannot be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.vendors.index');
    }
}
