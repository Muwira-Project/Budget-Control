<?php

namespace App\Livewire\FundTransfers;

use App\Models\CashAccount;
use App\Models\FundTransfer;
use App\Services\FundTransferService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public FundTransfer $transfer;

    public string $tanggal = '';

    public ?int $dariCashAccountId = null;

    public ?int $keCashAccountId = null;

    public string $nominal = '';

    public ?string $keterangan = null;

    public function mount(FundTransfer $fundTransfer): void
    {
        $this->transfer = $fundTransfer;
        $this->tanggal = $fundTransfer->tanggal ? $fundTransfer->tanggal->format('Y-m-d') : now()->format('Y-m-d');
        $this->dariCashAccountId = $fundTransfer->dari_cash_account_id;
        $this->keCashAccountId = $fundTransfer->ke_cash_account_id;
        $this->nominal = (string) (float) $fundTransfer->nominal;
        $this->keterangan = $fundTransfer->keterangan;
    }

    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    public function save(FundTransferService $service): void
    {
        $validated = Validator::make([
            'tanggal'              => $this->tanggal,
            'dari_cash_account_id' => $this->dariCashAccountId,
            'ke_cash_account_id'   => $this->keCashAccountId,
            'nominal'              => $this->nominal,
            'keterangan'           => $this->keterangan,
        ], [
            'tanggal'              => ['required', 'date'],
            'dari_cash_account_id' => ['required', 'exists:cash_accounts,id'],
            'ke_cash_account_id'   => ['required', 'exists:cash_accounts,id', 'different:dari_cash_account_id'],
            'nominal'              => ['required', 'numeric', 'min:1'],
            'keterangan'           => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $service->update($this->transfer, $validated);

        session()->flash('status', 'Fund transfer berhasil diperbarui.');

        $this->redirectRoute('fund-transfers.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.fund-transfers.edit');
    }
}
