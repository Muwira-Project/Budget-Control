<?php

namespace App\Livewire\Allokasis;

use App\Enums\AllocationStatus;
use App\Http\Requests\ProjectAkun\UpdateProjectAkunRequest;
use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Services\ProjectAkunService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public ProjectAkun $allocation;

    public ?int $projectId = null;

    public ?int $akunId = null;

    public string $budget = '';

    public string $allocationNominal = '';

    /**
     * Load the allocation being edited.
     *
     * Accepts both the route binding ({projectAkun}) and the legacy
     * parameter name (allocation) used by feature tests.
     */
    public function mount(?ProjectAkun $projectAkun = null, ?ProjectAkun $allocation = null): void
    {
        $projectAkun ??= $allocation;

        if (! $projectAkun) {
            abort(404);
        }

        if (! Gate::allows('manageDraft', $projectAkun)) {
            abort(403, 'Staff can only edit their own draft allocations.');
        }

        if ($projectAkun->status === AllocationStatus::Waiting || $projectAkun->isApproved()) {
            session()->flash('error', 'Allocation with status '.$projectAkun->status->label().' cannot be edited.');

            $this->redirectRoute('allokasis.index', navigate: true);

            return;
        }

        $this->allocation = $projectAkun;
        $this->projectId = $projectAkun->project_id;
        $this->akunId = $projectAkun->akun_id;
        $this->budget = $projectAkun->budget ?? '';
        $this->allocationNominal = $projectAkun->allocation ?? '';
    }

    /**
     * Update the allocation and move it back to draft.
     */
    public function save(ProjectAkunService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'akun_id' => $this->akunId,
                'budget' => $this->budget,
                'allocation' => $this->allocationNominal,
            ],
            (new UpdateProjectAkunRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $this->akunId)
                    ->where('id', '!=', $this->allocation->id)
                    ->exists()) {
                $validator->errors()->add('akun_id', 'This account is already allocated to this project.');
            }

            if ($this->projectId === null
                && $this->akunId !== null
                && ProjectAkun::whereNull('project_id')
                    ->where('akun_id', $this->akunId)
                    ->where('id', '!=', $this->allocation->id)
                    ->exists()) {
                $validator->errors()->add('akun_id', 'This account is already allocated as a non-project allocation.');
            }
        });

        $validated = $validator->validate();

        $service->update($this->allocation, $validated);

        $this->allocation->update(['status' => AllocationStatus::Draft]);

        session()->flash('status', 'Allocation updated successfully.');

        $this->redirectRoute('allokasis.index', navigate: true);
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
     * The master akuns not yet allocated to the selected project.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::query()
            ->when($this->projectId, function ($query) {
                $query->whereNotIn('id', ProjectAkun::where('project_id', $this->projectId)
                    ->when(isset($this->allocation), fn ($q) => $q->where('id', '!=', $this->allocation->id))
                    ->pluck('akun_id'));
            })
            ->orderBy('kode_akun')
            ->get();
    }

    /**
     * Render the allocation edit page.
     */
    public function render()
    {
        return view('livewire.allokasis.edit');
    }
}
