<?php

namespace App\Livewire\Cashflows;

use App\Http\Requests\Cashflow\StoreCashflowRequest;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
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

    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

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
     * Update party items when party type changes.
     */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
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
                'project_id' => $this->projectId,
                'pihak_type_id' => $this->pihakTypeId,
                'pihak_item_id' => $this->pihakItemId,
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
     * Projects for tagging manual cash entries.
     */
    public function projects()
    {
        return Project::query()->orderBy('nama')->get();
    }

    /**
     * Master party types for tagging (filter by AR/AP flag based on jenis).
     */
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

    /**
     * Master party items for the selected type.
     */
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

    /**
     * Render the cashflow create page.
     */
    public function render()
    {
        return view('livewire.cashflows.create');
    }
}
