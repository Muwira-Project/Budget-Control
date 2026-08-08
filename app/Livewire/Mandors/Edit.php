<?php

namespace App\Livewire\Mandors;

use App\Http\Requests\Mandor\UpdateMandorRequest;
use App\Models\Mandor;
use App\Services\MandorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Mandor $mandor;

    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    public function mount(Mandor $mandor): void
    {
        $this->mandor = $mandor;
        $this->kode = $mandor->kode;
        $this->nama = $mandor->nama;
        $this->telepon = $mandor->telepon;
        $this->alamat = $mandor->alamat;
    }

    public function save(MandorService $service): void
    {
        $validated = $this->validate((new UpdateMandorRequest)->rules($this->mandor->id));

        $service->update($this->mandor, $validated);

        session()->flash('status', 'Mandor updated successfully.');

        $this->redirectRoute('mandors.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.mandors.edit');
    }
}
