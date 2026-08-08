<?php

namespace App\Livewire\Suppliers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Services\SupplierService;
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
     * Store a newly created supplier.
     */
    public function save(SupplierService $service): void
    {
        $validated = $this->validate((new StoreSupplierRequest)->rules());

        $service->create($validated);

        session()->flash('status', 'Supplier created successfully.');

        $this->redirectRoute('suppliers.index', navigate: true);
    }

    /**
     * Render the supplier create page.
     */
    public function render()
    {
        return view('livewire.suppliers.create');
    }
}
