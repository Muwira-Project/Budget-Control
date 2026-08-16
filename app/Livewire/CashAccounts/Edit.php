<?php

namespace App\Livewire\CashAccounts;

use App\Models\CashAccount;
use App\Services\CashAccountService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public CashAccount $cashAccount;

    public string $kode = '';

    public string $nama = '';

    public string $jenis = 'kas';

    public string $saldoAwal = '0';

    public bool $isDefault = false;

    public string $status = 'active';

    public ?string $keterangan = null;

    public function mount(CashAccount $cashAccount): void
    {
        $this->cashAccount = $cashAccount;
        $this->kode = $cashAccount->kode;
        $this->nama = $cashAccount->nama;
        $this->jenis = $cashAccount->jenis->value;
        $this->saldoAwal = (string) $cashAccount->saldo_awal;
        $this->isDefault = (bool) $cashAccount->is_default;
        $this->status = $cashAccount->status->value;
        $this->keterangan = $cashAccount->keterangan;
    }

    /**
     * Update the cash account.
     */
    public function save(CashAccountService $service): void
    {
        $validated = Validator::make([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'saldo_awal' => $this->saldoAwal,
            'is_default' => $this->isDefault,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
        ], [
            'kode' => ['required', 'string', 'max:50', 'unique:cash_accounts,kode,'.$this->cashAccount->id],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:kas,bank'],
            'saldo_awal' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['boolean'],
            'status' => ['required', 'in:active,inactive'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $service->update($this->cashAccount, [
            ...$validated,
            'is_default' => $this->isDefault,
        ]);

        session()->flash('status', 'Cash account updated.');

        $this->redirectRoute('cash-accounts.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.cash-accounts.edit');
    }
}
