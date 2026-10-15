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

    /** 'ar_ap' | 'direct' | 'create_new' — only relevant when scope = non_project */
    public string $linkMode = 'direct';

    // ── Project & party (direct mode) ─────────────────────────────
    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    // ── AR / AP invoice selection ─────────────────────────────────
    public ?int $receivableId = null;

    public ?int $payableId = null;

    // ── Create new AR/AP fields (create_new mode) ─────────────────
    public ?int $newPihakTypeId = null;

    public ?int $newPihakItemId = null;

    public string $newInvoiceNo = '';

    public string $newDueDate = '';

    public ?string $newJenisPajak = null;

    public ?string $newKeterangan = null;

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
        $this->linkMode       = '';
        $this->projectId      = null;
        $this->receivableId   = null;
        $this->payableId      = null;
        $this->pihakTypeId    = null;
        $this->pihakItemId    = null;
        $this->newPihakTypeId = null;
        $this->newPihakItemId = null;
        $this->newInvoiceNo   = '';
        $this->newDueDate     = '';
        $this->newJenisPajak  = null;
        $this->newKeterangan  = null;
        $this->nominal        = '';
    }

    /** When link mode changes, reset invoice & party fields. */
    public function updatedLinkMode(): void
    {
        $this->receivableId     = null;
        $this->payableId        = null;
        $this->pihakTypeId      = null;
        $this->pihakItemId      = null;
        $this->newPihakTypeId   = null;
        $this->newPihakItemId   = null;
        $this->newInvoiceNo     = '';
        $this->newDueDate       = '';
        $this->newJenisPajak    = null;
        $this->newKeterangan    = null;
        $this->nominal          = '';
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

    /** When new party type changes, reset new party item. */
    public function updatedNewPihakTypeId(): void
    {
        $this->newPihakItemId = null;
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

    /**
     * True when this entry should create a new AR/AP invoice
     * (non-project + create_new mode).
     */
    public function isCreateNewArAp(): bool
    {
        return $this->scope === 'non_project' && $this->linkMode === 'create_new';
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

    /** Party types for creating NEW AR/AP (create_new mode). */
    public function newPartyTypes()
    {
        return MasterType::query()
            ->where('aktif', true)
            ->when($this->jenis === 'masuk', fn ($q) => $q->where('flag_ar', true))
            ->when($this->jenis === 'keluar', fn ($q) => $q->where('flag_ap', true))
            ->orderBy('sort')
            ->orderBy('nama')
            ->get();
    }

    /** Party items for creating NEW AR/AP (create_new mode). */
    public function newPartyItems()
    {
        if (! $this->newPihakTypeId) {
            return collect();
        }

        return MasterItem::query()
            ->where('master_type_id', $this->newPihakTypeId)
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
                $this->scope === 'project',
                fn ($q) => $this->projectId ? $q->where('project_id', $this->projectId) : $q->whereRaw('1 = 0'),
                fn ($q) => $this->scope === 'non_project' ? $q->whereNull('project_id') : $q
            )
            ->with(['project'])
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Outstanding AP invoices (not fully paid).
     * Filtered by project when scope = project and a project is selected.
     * Shows non-project outstanding when scope = non_project.
     */
    public function outstandingPayables()
    {
        return Payable::query()
            ->whereColumn('nominal_dibayar', '<', 'nominal')
            ->when(
                $this->scope === 'project',
                fn ($q) => $this->projectId ? $q->where('project_id', $this->projectId) : $q->whereRaw('1 = 0'),
                fn ($q) => $this->scope === 'non_project' ? $q->whereNull('project_id') : $q
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

        if ($this->scope === 'project') {
            $this->validate(
                ['projectId' => 'required|exists:projects,id'],
                ['projectId.required' => 'Silakan pilih project terlebih dahulu.']
            );
        }

        if ($this->isLinkedArAp()) {
            $this->saveAsArApSettlement($paymentService);
        } elseif ($this->isCreateNewArAp()) {
            $this->saveAsCreateNewArAp($cashflowService);
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
            $this->validate(
                ['receivableId' => 'required|exists:receivables,id'],
                ['receivableId.required' => 'Silakan pilih invoice AR yang akan dilunasi.']
            );

            $receivable = Receivable::findOrFail($this->receivableId);

            $paymentService->createForReceivable($receivable, [
                'tanggal'         => $this->tanggal,
                'nominal'         => $this->nominal,
                'keterangan'      => $this->keterangan,
                'cash_account_id' => $this->cashAccountId,
            ]);

            session()->flash('status', 'Penerimaan AR berhasil dicatat. Saldo piutang telah diperbarui.');
        } else {
            $this->validate(
                ['payableId' => 'required|exists:payables,id'],
                ['payableId.required' => 'Silakan pilih invoice AP yang akan dibayar.']
            );

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

    /**
     * Path C — create new AR/AP invoice + linked Cashflow (non-project only).
     * Creates Receivable (masuk) or Payable (keluar) and links Cashflow via FK.
     */
    private function saveAsCreateNewArAp(CashflowService $cashflowService): void
    {
        $this->validate([
            'newPihakTypeId' => 'required|exists:master_types,id',
            'newPihakItemId' => 'required|exists:master_items,id',
            'newInvoiceNo'   => 'required|string|max:255',
            'newDueDate'     => 'nullable|date',
        ]);

        if ($this->jenis === 'masuk') {
            // Validate AR party
            $partyItem = \App\Models\MasterItem::findOrFail($this->newPihakItemId);
            if (! $partyItem->flag_ar) {
                $this->addError('newPihakTypeId', 'Pihak ini tidak diperbolehkan untuk transaksi AR.');
                return;
            }

            // Check duplicate invoice
            if (\App\Models\Receivable::where('nomor_invoice', $this->newInvoiceNo)->exists()) {
                $this->addError('newInvoiceNo', "Invoice '{$this->newInvoiceNo}' sudah digunakan.");
                return;
            }

            // Create Receivable
            $receivable = \App\Models\Receivable::create([
                'project_id'       => null,
                'pihak_type_id'    => $this->newPihakTypeId,
                'pihak_item_id'    => $this->newPihakItemId,
                'tanggal'          => $this->tanggal,
                'nomor_invoice'    => $this->newInvoiceNo,
                'jatuh_tempo'      => $this->newDueDate ?: null,
                'nominal'          => $this->nominal,
                'nominal_dibayar'  => $this->nominal, // fully paid since cash received
                'keterangan'       => $this->newKeterangan,
            ]);

            // Create linked Cashflow
            $sumber = 'pendapatan';
            $validated = Validator::make(
                [
                    'tanggal'         => $this->tanggal,
                    'jenis'           => 'masuk',
                    'sumber'          => $sumber,
                    'nominal'         => $this->nominal,
                    'keterangan'      => $this->keterangan ?? $this->newKeterangan,
                    'cash_account_id' => $this->cashAccountId,
                    'akun_id'         => null,
                    'project_id'      => null,
                    'pihak_type_id'   => $this->newPihakTypeId,
                    'pihak_item_id'   => $this->newPihakItemId,
                    'receivable_id'   => $receivable->id,
                    'status'          => 'posted',
                ],
                (new \App\Http\Requests\Cashflow\StoreCashflowRequest)->rules(),
            )->validate();

            $cashflowService->create($validated);

            session()->flash('status', 'Piutang baru + Cash In berhasil dicatat.');

        } else {
            // Validate AP party
            $partyItem = \App\Models\MasterItem::findOrFail($this->newPihakItemId);
            if (! $partyItem->flag_ap) {
                $this->addError('newPihakTypeId', 'Pihak ini tidak diperbolehkan untuk transaksi AP.');
                return;
            }

            // Check duplicate invoice
            if (\App\Models\Payable::where('nomor_invoice', $this->newInvoiceNo)->exists()) {
                $this->addError('newInvoiceNo', "Invoice '{$this->newInvoiceNo}' sudah digunakan.");
                return;
            }

            // Create Payable
            $payable = \App\Models\Payable::create([
                'project_id'      => null,
                'akun_id'         => $this->akunId,
                'pihak_type_id'   => $this->newPihakTypeId,
                'pihak_item_id'   => $this->newPihakItemId,
                'tanggal'         => $this->tanggal,
                'nomor_invoice'   => $this->newInvoiceNo,
                'jatuh_tempo'     => $this->newDueDate ?: null,
                'nominal'         => $this->nominal,
                'jenis_pajak'     => $this->newJenisPajak,
                'pajak_include'   => true,
                'nominal_dibayar' => $this->nominal, // fully paid since cash paid out
                'keterangan'      => $this->newKeterangan,
            ]);

            // Create linked Cashflow
            $sumber = 'pengeluaran_lain';
            $validated = Validator::make(
                [
                    'tanggal'         => $this->tanggal,
                    'jenis'           => 'keluar',
                    'sumber'          => $sumber,
                    'nominal'         => $this->nominal,
                    'keterangan'      => $this->keterangan ?? $this->newKeterangan,
                    'cash_account_id' => $this->cashAccountId,
                    'akun_id'         => $this->akunId,
                    'project_id'      => null,
                    'pihak_type_id'   => $this->newPihakTypeId,
                    'pihak_item_id'   => $this->newPihakItemId,
                    'payable_id'      => $payable->id,
                    'status'          => 'posted',
                ],
                (new \App\Http\Requests\Cashflow\StoreCashflowRequest)->rules(),
            )->validate();

            $cashflowService->create($validated);

            session()->flash('status', 'Hutang baru + Cash Out berhasil dicatat.');
        }
    }

    public function render()
    {
        return view('livewire.cashflows.create');
    }
}
