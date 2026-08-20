<?php

namespace App\Livewire\Payables;

use App\Http\Requests\Payable\StorePayableRequest;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Services\PayableService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public ?int $akunId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    public string $tanggal = '';

    public string $nomorInvoice = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public string $jenisPajak = '';

    public bool $pajakInclude = true;

    public ?string $keterangan = null;

    /**
     * Set the default payable date.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Store a newly created payable.
     */
    public function save(PayableService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'akun_id' => $this->akunId,
                'pihak_type_id' => $this->pihakTypeId,
                'pihak_item_id' => $this->pihakItemId,
                'tanggal' => $this->tanggal,
                'nomor_invoice' => $this->nomorInvoice !== '' ? $this->nomorInvoice : null,
                'jatuh_tempo' => $this->jatuhTempo !== '' ? $this->jatuhTempo : null,
                'nominal' => $this->nominal,
                'jenis_pajak' => $this->jenisPajak !== '' ? $this->jenisPajak : null,
                'pajak_include' => $this->pajakInclude,
                'keterangan' => $this->keterangan,
            ],
            (new StorePayableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->pihakTypeId === null || $this->pihakItemId === null) {
                $validator->errors()->add('pihak_item_id', 'Pilih salah satu pihak (vendor/supplier/mandor/investor).');
            }

            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $this->akunId)
                    ->where('status', 'approved')
                    ->doesntExist()) {
                $validator->errors()->add('akun_id', 'Account must be allocated (approved) to the selected project.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Payable created successfully.');

        $this->redirectRoute('payables.index', navigate: true);
    }

    /**
     * Reset the party selection when the party type changes.
     */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
    }

    /**
     * The projects available for selection.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * The approved allocations for the selected project.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::query()
            ->when($this->projectId, fn ($query) => $query->whereIn('id', ProjectAkun::where('project_id', $this->projectId)->where('status', 'approved')->pluck('akun_id')))
            ->orderBy('kode_akun')
            ->get();
    }

    /**
     * Budget context for the selected project+akun: allocation, realized,
     * remaining allocation, and available budget — shown as a hint in the form.
     *
     * @return array<string, float|string>|null
     */
    #[Computed]
    public function budgetInfo(): ?array
    {
        if ($this->projectId === null || $this->akunId === null) {
            return null;
        }

        $allocation = ProjectAkun::query()
            ->where('project_id', $this->projectId)
            ->where('akun_id', $this->akunId)
            ->where('status', 'approved')
            ->first();

        if (! $allocation) {
            return null;
        }

        $remaining = (float) $allocation->remaining_allocation;

        return [
            'allocation' => (float) $allocation->allocation,
            'realized' => (float) $allocation->total_realisasi,
            'remaining' => $remaining,
            'available' => (float) $allocation->available_budget,
            'over' => $remaining < 0,
        ];
    }

    /**
     * Party types flagged as AP (hutang) — available for the payable party.
     */
    #[Computed]
    public function partyTypes()
    {
        return MasterType::query()
            ->where('aktif', true)
            ->where(fn ($query) => $query->where('flag_ap', true)->orWhereNull('flag_ap'))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Party items under the selected type that may be picked as an AP party
     * (flag_ap null/true) and are active.
     */
    #[Computed]
    public function partyItems()
    {
        if ($this->pihakTypeId === null) {
            return collect();
        }

        return MasterItem::query()
            ->where('master_type_id', $this->pihakTypeId)
            ->where('aktif', true)
            ->where(fn ($query) => $query->where('flag_ap', true)->orWhereNull('flag_ap'))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Render the payable create page.
     */
    public function render()
    {
        return view('livewire.payables.create');
    }
}
