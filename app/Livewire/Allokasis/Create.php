<?php

namespace App\Livewire\Allokasis;

use App\Http\Requests\ProjectAkun\StoreProjectAkunRequest;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Services\ProjectAkunService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public ?int $akunId = null;

    public string $budget = '';

    public string $allocationNominal = '';

    /**
     * Reset the akun selection and prefill when the project changes.
     */
    public function updatedProjectId(): void
    {
        $this->akunId = null;
        $this->budget = '';
        $this->allocationNominal = '';
    }

    /**
     * Prefill the budget from the project budget plan when an akun is selected.
     */
    public function updatedAkunId(): void
    {
        if ($this->projectId === null || $this->akunId === null) {
            return;
        }

        $plan = BudgetPlan::where('project_id', $this->projectId)
            ->latest('periode')
            ->first();

        if (! $plan) {
            return;
        }

        $item = BudgetPlanItem::where('budget_plan_id', $plan->id)
            ->where('akun_id', $this->akunId)
            ->first();

        if ($item) {
            $this->budget = (string) $item->nominal;
            $this->allocationNominal = (string) $item->nominal;
        }
    }

    /**
     * Store a newly created allocation draft.
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
            (new StoreProjectAkunRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)->where('akun_id', $this->akunId)->exists()) {
                $validator->errors()->add('akun_id', 'This account is already allocated to this project.');
            }

            if ($this->projectId === null
                && $this->akunId !== null
                && ProjectAkun::whereNull('project_id')->where('akun_id', $this->akunId)->exists()) {
                $validator->errors()->add('akun_id', 'This account is already allocated as a non-project allocation.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Allocation draft created successfully.');

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
     * The master akuns not yet allocated to the selected project,
     * or all akuns when a non-project allocation is being created.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::query()
            ->when($this->projectId, fn ($query) => $query->whereNotIn('id', ProjectAkun::where('project_id', $this->projectId)->pluck('akun_id')))
            ->when($this->projectId === null, fn ($query) => $query->whereNotIn('id', ProjectAkun::whereNull('project_id')->pluck('akun_id')))
            ->orderBy('kode_akun')
            ->get();
    }

    /**
     * Render the allocation create page.
     */
    public function render()
    {
        return view('livewire.allokasis.create');
    }
}
