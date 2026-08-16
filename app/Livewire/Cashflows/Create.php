<?php

namespace App\Livewire\Cashflows;

use App\Http\Requests\Cashflow\StoreCashflowRequest;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Services\CashflowService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $tanggal = '';

    public string $jenis = 'masuk';

    public string $nominal = '';

    public ?string $keterangan = null;

    public ?int $cashAccountId = null;

    public ?int $akunId = null;

    /**
     * Set the default entry date and type from the requested mode.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
        $this->jenis = request('mode') === 'keluar' ? 'keluar' : 'masuk';
        $this->cashAccountId = CashAccount::defaultId();
    }

    /**
     * Store a manually recorded cash entry (cash in or cash out).
     */
    public function save(CashflowService $service): void
    {
        $sumber = $this->jenis === 'masuk' ? 'pendapatan' : 'pengeluaran_lain';

        $validated = Validator::make(
            [
                'tanggal' => $this->tanggal,
                'jenis' => $this->jenis,
                'sumber' => $sumber,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
                'cash_account_id' => $this->cashAccountId,
                'akun_id' => $this->jenis === 'keluar' ? $this->akunId : null,
                'status' => 'draft',
            ],
            (new StoreCashflowRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', ucfirst(str_replace('_', ' ', $sumber)).' saved as draft. Submit it for admin approval.');

        $this->redirectRoute('cashflows.index', navigate: true);
    }

    /**
     * The active cash accounts for the lokasi dana field.
     */
    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    /**
     * The expense/revenue accounts (COA) for cash-out entries.
     */
    public function akuns()
    {
        return Akun::query()->orderBy('kode_akun')->get();
    }

    /**
     * Render the cashflow create page.
     */
    public function render()
    {
        return view('livewire.cashflows.create');
    }
}
