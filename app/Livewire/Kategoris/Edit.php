<?php

namespace App\Livewire\Kategoris;

use App\Http\Requests\Kategori\UpdateKategoriRequest;
use App\Models\Kategori;
use App\Services\KategoriService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Kategori $kategori;

    public string $kode = '';

    public string $nama = '';

    /**
     * Load the kategori being edited.
     */
    public function mount(Kategori $kategori): void
    {
        $this->kategori = $kategori;
        $this->kode = $kategori->kode;
        $this->nama = $kategori->nama;
    }

    /**
     * Update the kategori.
     */
    public function save(KategoriService $service): void
    {
        $validated = $this->validate((new UpdateKategoriRequest)->rules($this->kategori->id));

        $service->update($this->kategori, $validated);

        session()->flash('status', 'Category updated successfully.');

        $this->redirectRoute('kategoris.index', navigate: true);
    }

    /**
     * Render the kategori edit page.
     */
    public function render()
    {
        return view('livewire.kategoris.edit');
    }
}
