<?php

namespace App\Livewire\Vendors;

use App\Http\Requests\Vendor\StoreVendorRequest;
use App\Services\VendorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    /**
     * Store a newly created vendor.
     */
    public function save(VendorService $service): void
    {
        $validated = $this->validate((new StoreVendorRequest)->rules());

        $service->create($validated);

        session()->flash('status', 'Vendor created successfully.');

        $this->redirectRoute('vendors.index', navigate: true);
    }

    /**
     * Render the vendor create page.
     */
    public function render()
    {
        return view('livewire.vendors.create');
    }
}
