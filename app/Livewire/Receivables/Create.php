<?php

namespace App\Livewire\Receivables;

use App\Http\Requests\Receivable\StoreReceivableRequest;
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

    public string $tanggal = '';

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

    /**
     * Store a newly created receivable.
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
            (new StoreReceivableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && Receivable::where('project_id', $this->projectId)->exists()) {
                $validator->errors()->add('project_id', 'This project already has a receivable.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Receivable created successfully.');

        $this->redirectRoute('receivables.index', navigate: true);
    }

    /**
     * The projects that do not have a receivable yet.
     */
    #[Computed]
    public function projects()
    {
        return Project::whereDoesntHave('receivable')->orderBy('nama')->get();
    }

    /**
     * Render the receivable create page.
     */
    public function render()
    {
        return view('livewire.receivables.create');
    }
}
