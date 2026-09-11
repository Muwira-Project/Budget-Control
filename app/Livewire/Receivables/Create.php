<?php

namespace App\Livewire\Receivables;

use App\Http\Requests\Receivable\StoreReceivableRequest;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    public string $tanggal = '';

    public string $nomorInvoice = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Set the default receivable date.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Suggest the nominal from the project contract value when the project changes.
     */
    public function updatedProjectId(): void
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        if ($project) {
            $this->nominal = (string) $project->nilai_total;
        }
    }

    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
    }

    /**
     * Store a newly created receivable.
     */
    public function save(ReceivableService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => ! empty($this->projectId) ? (int) $this->projectId : null,
                'pihak_type_id' => $this->pihakTypeId,
                'pihak_item_id' => $this->pihakItemId,
                'tanggal' => $this->tanggal,
                'nomor_invoice' => $this->nomorInvoice !== '' ? $this->nomorInvoice : null,
                'jatuh_tempo' => $this->jatuhTempo !== '' ? $this->jatuhTempo : null,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            (new StoreReceivableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            // Only check duplicate per project when a project is selected
            if (! empty($this->projectId)
                && Receivable::where('project_id', $this->projectId)->exists()) {
                $validator->errors()->add('project_id', 'Proyek ini sudah memiliki receivable.');
            }

            if (($this->pihakTypeId === null) !== ($this->pihakItemId === null)) {
                $validator->errors()->add('pihak_item_id', 'Pilih pihak (type + item) atau kosongkan keduanya.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Receivable created successfully.');

        $this->redirectRoute('receivables.index', navigate: true);
    }

    /**
     * All active projects (project_id is now optional for non-project AR).
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * Party types flagged as AR (piutang) — available for the receivable party.
     */
    #[Computed]
    public function partyTypes()
    {
        return MasterType::query()
            ->where('aktif', true)
            ->where(fn ($query) => $query->where('flag_ar', true)->orWhereNull('flag_ar'))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Party items under the selected type that may be picked as an AR party
     * (flag_ar null/true) and are active.
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
            ->where(fn ($query) => $query->where('flag_ar', true)->orWhereNull('flag_ar'))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Render the receivable create page.
     */
    public function render()
    {
        return view('livewire.receivables.create');
    }
}
