<?php

namespace App\Livewire\Suppliers;

use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Supplier $supplier;

    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    /**
     * Load the supplier being edited.
     */
    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->kode = $supplier->kode;
        $this->nama = $supplier->nama;
        $this->telepon = $supplier->telepon;
        $this->alamat = $supplier->alamat;
    }

    /**
     * Update the supplier.
     */
    public function save(SupplierService $service): void
    {
        $validated = $this->validate((new UpdateSupplierRequest)->rules($this->supplier->id));

        $service->update($this->supplier, $validated);

        session()->flash('status', 'Supplier updated successfully.');

        $this->redirectRoute('suppliers.index', navigate: true);
    }

    /**
     * Render the supplier edit page.
     */
    public function render()
    {
        return view('livewire.suppliers.edit');
    }
}
