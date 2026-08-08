<?php

namespace App\Livewire\Mandors;

use App\Http\Requests\Mandor\StoreMandorRequest;
use App\Services\MandorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    public function save(MandorService $service): void
    {
        $validated = $this->validate((new StoreMandorRequest)->rules());

        $service->create($validated);

        session()->flash('status', 'Mandor created successfully.');

        $this->redirectRoute('mandors.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.mandors.create');
    }
}
