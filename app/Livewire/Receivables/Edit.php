<?php

namespace App\Livewire\Receivables;

use App\Http\Requests\Receivable\UpdateReceivableRequest;
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
class Edit extends Component
{
    public Receivable $receivable;

    public ?int $projectId = null;

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    public string $tanggal = '';

    public string $nomorInvoice = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Load the receivable being edited.
     */
    public function mount(Receivable $receivable): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only admins can edit receivables.');

        $this->receivable = $receivable;
        $this->projectId = $receivable->project_id;
        $this->pihakTypeId = $receivable->pihak_type_id;
        $this->pihakItemId = $receivable->pihak_item_id;
        $this->tanggal = $receivable->tanggal->format('Y-m-d');
        $this->nomorInvoice = $receivable->nomor_invoice ?? '';
        $this->jatuhTempo = $receivable->jatuh_tempo?->format('Y-m-d') ?? '';
        $this->nominal = $receivable->nominal ?? '';
        $this->keterangan = $receivable->keterangan;
    }

    /**
     * Update the receivable.
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
            (new UpdateReceivableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            // Only validate duplicate project when a project is selected
            if (! empty($this->projectId)
                && Receivable::where('project_id', $this->projectId)
                    ->where('id', '!=', $this->receivable->id)
                    ->exists()) {
                $validator->errors()->add('project_id', 'Proyek ini sudah memiliki receivable.');
            }

            if (($this->pihakTypeId === null) !== ($this->pihakItemId === null)) {
                $validator->errors()->add('pihak_item_id', 'Pilih pihak (type + item) atau kosongkan keduanya.');
            }

            if ($this->nominal !== ''
                && (float) $this->nominal < (float) $this->receivable->nominal_dibayar) {
                $validator->errors()->add('nominal', 'Nominal cannot be lower than the amount already paid ('.number_format((float) $this->receivable->nominal_dibayar, 0, ',', '.').').');
            }
        });

        $validated = $validator->validate();

        $service->update($this->receivable, $validated);

        session()->flash('status', 'Receivable updated successfully.');

        $this->redirectRoute('receivables.index', navigate: true);
    }

    /**
     * Reset the party selection when the party type changes.
     */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
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
     * Render the receivable edit page.
     */
    public function render()
    {
        return view('livewire.receivables.edit');
    }
}
