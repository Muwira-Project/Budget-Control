<?php

namespace App\Livewire\Cashflows;

use App\Http\Requests\Cashflow\StoreCashflowRequest;
use App\Services\CashflowService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $tanggal = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Set the default entry date.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Store a manually recorded cash in (pendapatan).
     */
    public function save(CashflowService $service): void
    {
        $validated = Validator::make(
            [
                'tanggal' => $this->tanggal,
                'jenis' => 'masuk',
                'sumber' => 'pendapatan',
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            (new StoreCashflowRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', 'Income recorded successfully.');

        $this->redirectRoute('cashflows.index', navigate: true);
    }

    /**
     * Render the cashflow create page.
     */
    public function render()
    {
        return view('livewire.cashflows.create');
    }
}
