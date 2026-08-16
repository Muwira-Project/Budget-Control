<?php

namespace App\Livewire\CashAccounts;

use App\Services\CashAccountService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public string $jenis = 'kas';

    public string $saldoAwal = '0';

    public bool $isDefault = false;

    public string $status = 'active';

    public ?string $keterangan = null;

    /**
     * Store the new cash account.
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
            'kode' => ['required', 'string', 'max:50', 'unique:cash_accounts,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:kas,bank'],
            'saldo_awal' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['boolean'],
            'status' => ['required', 'in:active,inactive'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $service->create([
            ...$validated,
            'is_default' => $this->isDefault,
        ]);

        session()->flash('status', 'Cash account created.');

        $this->redirectRoute('cash-accounts.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.cash-accounts.create');
    }
}
