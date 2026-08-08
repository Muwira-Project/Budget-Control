<?php

namespace App\Livewire\Vendors;

use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Vendor $vendor;

    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    /**
     * Load the vendor being edited.
     */
    public function mount(Vendor $vendor): void
    {
        $this->vendor = $vendor;
        $this->kode = $vendor->kode;
        $this->nama = $vendor->nama;
        $this->telepon = $vendor->telepon;
        $this->alamat = $vendor->alamat;
    }

    /**
     * Update the vendor.
     */
    public function save(VendorService $service): void
    {
        $validated = $this->validate((new UpdateVendorRequest)->rules($this->vendor->id));

        $service->update($this->vendor, $validated);

        session()->flash('status', 'Vendor updated successfully.');

        $this->redirectRoute('vendors.index', navigate: true);
    }

    /**
     * Render the vendor edit page.
     */
    public function render()
    {
        return view('livewire.vendors.edit');
    }
}
