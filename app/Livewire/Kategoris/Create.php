<?php

namespace App\Livewire\Kategoris;

use App\Http\Requests\Kategori\StoreKategoriRequest;
use App\Services\KategoriService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    /**
     * Store a newly created kategori.
     */
    public function save(KategoriService $service): void
    {
        $validated = $this->validate((new StoreKategoriRequest)->rules());

        $service->create($validated);

        session()->flash('status', 'Category created successfully.');

        $this->redirectRoute('kategoris.index', navigate: true);
    }

    /**
     * Render the kategori create page.
     */
    public function render()
    {
        return view('livewire.kategoris.create');
    }
}
