<?php

namespace App\Livewire\Cashflows;

use App\Http\Requests\Cashflow\StoreCashflowRequest;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Receivable;
use App\Services\CashflowService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    // ── Core fields ───────────────────────────────────────────────
    public string $tanggal = '';

    public string $jenis = 'masuk';

    public string $nominal = '';

    public ?string $keterangan = null;

    public ?int $cashAccountId = null;

    public ?int $akunId = null;

    // ── Scope & link mode ─────────────────────────────────────────
    /** 'project' | 'non_project' */
    public string $scope = 'non_project';

    /** 'ar_ap' | 'direct'  — only relevant when scope = non_project */
    public string $linkMode = 'direct';

    // ── Project & party (direct mode) ─────────────────────────────
    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    // ── AR / AP invoice selection ─────────────────────────────────
    public ?int $receivableId = null;

    public ?int $payableId = null;

    // ─────────────────────────────────────────────────────────────

    public function mount(?string $mode = null): void
    {
        $this->tanggal       = now()->format('Y-m-d');
        $this->jenis         = ($mode ?? request('mode')) === 'keluar' ? 'keluar' : 'masuk';
        $this->cashAccountId = CashAccount::defaultId();
    }

    // ── Watchers ──────────────────────────────────────────────────

    /** When direction changes, reset invoice & nominal. */
    public function updatedJenis(): void
    {
        $this->receivableId = null;
        $this->payableId    = null;
        $this->nominal      = '';
        $this->pihakTypeId  = null;
        $this->pihakItemId  = null;
    }

    /** When scope changes, reset everything below. */
    public function updatedScope(): void
    {
        $this->linkMode     = '';
        $this->projectId    = null;
        $this->receivableId = null;
        $this->payableId    = null;
        $this->pihakTypeId  = null;
        $this->pihakItemId  = null;
        $this->nominal      = '';
    }

    /** When link mode changes, reset invoice & party fields. */
    public function updatedLinkMode(): void
    {
        $this->receivableId = null;
        $this->payableId    = null;
        $this->pihakTypeId  = null;
        $this->pihakItemId  = null;
        $this->nominal      = '';
    }

    /** When project changes, reset invoice & nominal. */
    public function updatedProjectId(): void
    {
        $this->receivableId = null;
        $this->payableId    = null;
        $this->nominal      = '';
    }

    /** When party type changes, reset party item. */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
    }

    /** Auto-fill nominal from AR outstanding balance. */
    public function updatedReceivableId(): void
    {
        if ($this->receivableId) {
            $receivable    = Receivable::find($this->receivableId);
            $this->nominal = $receivable ? (string) $receivable->sisa : '';
        } else {
            $this->nominal = '';
        }
    }

    /** Auto-fill nominal from AP outstanding balance. */
    public function updatedPayableId(): void
    {
        if ($this->payableId) {
            $payable       = Payable::find($this->payableId);
            $this->nominal = $payable ? (string) $payable->sisa : '';
        } else {
            $this->nominal = '';
        }
    }

    // ── Computed helpers ──────────────────────────────────────────

    /**
     * True when this entry should settle an AR or AP invoice
     * (project mode always links; non-project only when linkMode = ar_ap).
     */
    public function isLinkedArAp(): bool
    {
        return $this->scope === 'project'
            || ($this->scope === 'non_project' && $this->linkMode === 'ar_ap');
    }

    // ── Data providers ────────────────────────────────────────────

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
        return Project::query()->orderBy('kode')->get();
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

    /**
     * Outstanding AR invoices (not fully paid).
     * Filtered by project when scope = project and a project is selected.
     * Shows all outstanding when scope = non_project.
     */
    public function outstandingReceivables()
    {
        return Receivable::query()
            ->whereColumn('nominal_dibayar', '<', 'nominal')
            ->when(
                $this->scope === 'project' && $this->projectId,
                fn ($q) => $q->where('project_id', $this->projectId)
            )
            ->with(['project'])
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Outstanding AP invoices (not fully paid).
     * Filtered by project when scope = project and a project is selected.
     * Shows all outstanding when scope = non_project.
     */
    public function outstandingPayables()
    {
        return Payable::query()
            ->whereColumn('nominal_dibayar', '<', 'nominal')
            ->when(
                $this->scope === 'project' && $this->projectId,
                fn ($q) => $q->where('project_id', $this->projectId)
            )
            ->with(['project', 'pihakItem'])
            ->orderByDesc('tanggal')
            ->get();
    }

    // ── Save ──────────────────────────────────────────────────────

    public function save(CashflowService $cashflowService, PaymentService $paymentService): void
    {
        // ── Base validation (always required) ──
        $this->validate([
            'tanggal'       => 'required|date',
            'jenis'         => 'required|in:masuk,keluar',
            'nominal'       => 'required|numeric|min:0.01',
            'cashAccountId' => 'nullable|exists:cash_accounts,id',
            'scope'         => 'required|in:project,non_project',
        ]);

        if ($this->isLinkedArAp()) {
            $this->saveAsArApSettlement($paymentService);
        } else {
            $this->saveAsDirect($cashflowService);
        }

        $this->redirectRoute('cashflows.index', navigate: true);
    }

    /**
     * Path A — settle an existing AR or AP invoice.
     * Delegates to PaymentService which creates Payment + Cashflow atomically.
     */
    private function saveAsArApSettlement(PaymentService $paymentService): void
    {
        if ($this->jenis === 'masuk') {
            $this->validate(['receivableId' => 'required|exists:receivables,id']);

            $receivable = Receivable::findOrFail($this->receivableId);

            $paymentService->createForReceivable($receivable, [
                'tanggal'         => $this->tanggal,
                'nominal'         => $this->nominal,
                'keterangan'      => $this->keterangan,
                'cash_account_id' => $this->cashAccountId,
            ]);

            session()->flash('status', 'Penerimaan AR berhasil dicatat. Saldo piutang telah diperbarui.');
        } else {
            $this->validate(['payableId' => 'required|exists:payables,id']);

            $payable = Payable::findOrFail($this->payableId);

            $paymentService->createForPayable($payable, [
                'tanggal'         => $this->tanggal,
                'nominal'         => $this->nominal,
                'keterangan'      => $this->keterangan,
                'cash_account_id' => $this->cashAccountId,
            ]);

            session()->flash('status', 'Pembayaran AP berhasil dicatat. Saldo hutang telah diperbarui.');
        }
    }

    /**
     * Path B — direct manual cash entry (no AR/AP linkage).
     * Delegates to CashflowService (existing behaviour).
     */
    private function saveAsDirect(CashflowService $cashflowService): void
    {
        $sumber = $this->jenis === 'masuk' ? 'pendapatan' : 'pengeluaran_lain';

        $validated = Validator::make(
            [
                'tanggal'        => $this->tanggal,
                'jenis'          => $this->jenis,
                'sumber'         => $sumber,
                'nominal'        => $this->nominal,
                'keterangan'     => $this->keterangan,
                'cash_account_id'=> $this->cashAccountId,
                'akun_id'        => $this->jenis === 'keluar' ? $this->akunId : null,
                'project_id'     => null,
                'pihak_type_id'  => $this->pihakTypeId,
                'pihak_item_id'  => $this->pihakItemId,
                'status'         => 'posted',
            ],
            (new StoreCashflowRequest)->rules(),
        )->validate();

        $cashflowService->create($validated);

        session()->flash('status', ucfirst(str_replace('_', ' ', $sumber)).' saved successfully.');
    }

    public function render()
    {
        return view('livewire.cashflows.create');
    }
}
