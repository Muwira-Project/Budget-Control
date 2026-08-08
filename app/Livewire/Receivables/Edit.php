<?php

namespace App\Livewire\Receivables;

use App\Http\Requests\Receivable\UpdateReceivableRequest;
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

    public string $tanggal = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Load the receivable being edited.
     */
    public function mount(Receivable $receivable): void
    {
        $this->receivable = $receivable;
        $this->projectId = $receivable->project_id;
        $this->tanggal = $receivable->tanggal->format('Y-m-d');
        $this->jatuhTempo = $receivable->jatuh_tempo?->format('Y-m-d') ?? '';
        $this->nominal = $receivable->nominal;
        $this->keterangan = $receivable->keterangan;
    }

    /**
     * Update the receivable.
     */
    public function save(ReceivableService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'tanggal' => $this->tanggal,
                'jatuh_tempo' => $this->jatuhTempo !== '' ? $this->jatuhTempo : null,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            (new UpdateReceivableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && Receivable::where('project_id', $this->projectId)
                    ->where('id', '!=', $this->receivable->id)
                    ->exists()) {
                $validator->errors()->add('project_id', 'This project already has a receivable.');
            }
        });

        $validated = $validator->validate();

        $service->update($this->receivable, $validated);

        session()->flash('status', 'Receivable updated successfully.');

        $this->redirectRoute('receivables.index', navigate: true);
    }

    /**
     * The projects that do not have a receivable yet (excluding this one).
     */
    #[Computed]
    public function projects()
    {
        return Project::whereDoesntHave('receivable')
            ->orWhereHas('receivable', fn ($query) => $query->where('id', $this->receivable->id))
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
