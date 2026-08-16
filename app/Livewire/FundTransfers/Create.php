<?php

namespace App\Livewire\FundTransfers;

use App\Services\FundTransferService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $tanggal = '';

    public ?int $dariCashAccountId = null;

    public ?int $keCashAccountId = null;

    public string $nominal = '';

    public ?string $keterangan = null;

    public function mount(FundTransferService $service): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Store the fund transfer.
     */
    public function save(FundTransferService $service): void
    {
        $validated = Validator::make([
            'tanggal' => $this->tanggal,
            'dari_cash_account_id' => $this->dariCashAccountId,
            'ke_cash_account_id' => $this->keCashAccountId,
            'nominal' => $this->nominal,
            'keterangan' => $this->keterangan,
        ], [
            'tanggal' => ['required', 'date'],
            'dari_cash_account_id' => ['required', 'exists:cash_accounts,id'],
            'ke_cash_account_id' => ['required', 'exists:cash_accounts,id', 'different:dari_cash_account_id'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $service->create($validated);

        session()->flash('status', 'Fund transfer recorded.');

        $this->redirectRoute('fund-transfers.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.fund-transfers.create');
    }
}