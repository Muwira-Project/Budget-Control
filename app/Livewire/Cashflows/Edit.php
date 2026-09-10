<?php

namespace App\Livewire\Cashflows;

use App\Http\Requests\Cashflow\StoreCashflowRequest;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Services\CashflowService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Cashflow $cashflow;

    public string $tanggal = '';

    public string $jenis = 'masuk';

    public string $nominal = '';

    public ?string $keterangan = null;

    public ?int $cashAccountId = null;

    public ?int $akunId = null;

    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    /**
     * Mount and fill existing cashflow data.
     */
    public function mount(Cashflow $cashflow): void
    {
        $this->cashflow = $cashflow;
        $this->tanggal = $cashflow->tanggal ? $cashflow->tanggal->format('Y-m-d') : now()->format('Y-m-d');
        $this->jenis = $cashflow->jenis->value;
        $this->nominal = (string) (float) $cashflow->nominal;
        $this->keterangan = $cashflow->keterangan;
        $this->cashAccountId = $cashflow->cash_account_id;
        $this->akunId = $cashflow->akun_id;
        $this->projectId = $cashflow->project_id;
        $this->pihakTypeId = $cashflow->pihak_type_id;
        $this->pihakItemId = $cashflow->pihak_item_id;
    }

    /**
     * Reset party item when party type changes.
     */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
    }

    /**
     * Update the cashflow entry.
     */
    public function save(CashflowService $service): void
    {
        $sumber = $this->cashflow->sumber->value;

        $validated = Validator::make(
            [
                'tanggal'         => $this->tanggal,
                'jenis'           => $this->jenis,
                'sumber'          => $sumber,
                'nominal'         => $this->nominal,
                'keterangan'      => $this->keterangan,
                'cash_account_id' => $this->cashAccountId,
                'akun_id'         => $this->akunId,
                'project_id'      => $this->projectId,
                'pihak_type_id'   => $this->pihakTypeId,
                'pihak_item_id'   => $this->pihakItemId,
                'status'          => 'posted',
            ],
            (new StoreCashflowRequest)->rules(),
        )->validate();

        $service->update($this->cashflow, $validated);

        session()->flash('status', 'Transaksi '.($this->jenis === 'masuk' ? 'Cash In' : 'Cash Out').' berhasil diperbarui.');

        $this->redirectRoute('cashflows.index', navigate: true);
    }

    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    public function akuns()
    {
        return Akun::query()->orderBy('kode_akun')->get();
    }

    public function projects()
    {
        return Project::query()->orderBy('nama')->get();
    }

    public function pihakTypes()
    {
        return MasterType::query()
            ->where('aktif', true)
            ->when($this->jenis === 'masuk', fn ($q) => $q->where('flag_ar', true))
            ->when($this->jenis === 'keluar', fn ($q) => $q->where('flag_ap', true))
            ->orderBy('sort')
            ->orderBy('nama')
            ->get();
    }

    public function pihakItems()
    {
        if (! $this->pihakTypeId) {
            return collect();
        }

        return MasterItem::query()
            ->where('master_type_id', $this->pihakTypeId)
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();
    }

    public function render()
    {
        return view('livewire.cashflows.edit');
    }
}
