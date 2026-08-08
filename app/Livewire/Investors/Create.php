<?php

namespace App\Livewire\Investors;

use App\Http\Requests\Investor\StoreInvestorRequest;
use App\Services\InvestorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public ?string $telepon = null;

    public ?string $alamat = null;

    public function save(InvestorService $service): void
    {
        $validated = $this->validate((new StoreInvestorRequest)->rules());

        $service->create($validated);

        session()->flash('status', 'Investor created successfully.');

        $this->redirectRoute('investors.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.investors.create');
    }
}
