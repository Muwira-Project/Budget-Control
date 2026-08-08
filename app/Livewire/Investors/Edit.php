<?php

namespace App\Livewire\Investors;

use App\Http\Requests\Investor\UpdateInvestorRequest;
use App\Models\Investor;
use App\Services\InvestorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Investor $investor;

    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    public function mount(Investor $investor): void
    {
        $this->investor = $investor;
        $this->kode = $investor->kode;
        $this->nama = $investor->nama;
        $this->telepon = $investor->telepon;
        $this->alamat = $investor->alamat;
    }

    public function save(InvestorService $service): void
    {
        $validated = $this->validate((new UpdateInvestorRequest)->rules($this->investor->id));

        $service->update($this->investor, $validated);

        session()->flash('status', 'Investor updated successfully.');

        $this->redirectRoute('investors.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.investors.edit');
    }
}
