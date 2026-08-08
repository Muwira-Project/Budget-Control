<?php

namespace App\Livewire\BudgetPlans;

use App\Http\Requests\BudgetPlan\StoreBudgetPlanRequest;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\Project;
use App\Services\BudgetPlanService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public string $periode = '';

    public string $estimasiPendapatan = '';

    public string $targetLaba = '';

    public array $items = [];

    /**
     * Set the default periode and start with one rincian row.
     */
    public function mount(): void
    {
        $this->periode = now()->format('Y-m');
        $this->addItem();
    }

    /**
     * Add an empty rincian row.
     */
    public function addItem(): void
    {
        $this->items[] = ['akun_id' => '', 'nominal' => '', 'tanggal_mulai' => '', 'tanggal_selesai' => ''];
    }

    /**
     * Remove a rincian row.
     */
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Store a newly created budget plan.
     */
    public function save(BudgetPlanService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'periode' => $this->periode,
                'estimasi_pendapatan' => $this->estimasiPendapatan,
                'target_laba' => $this->targetLaba !== '' ? $this->targetLaba : null,
                'items' => $this->items,
            ],
            (new StoreBudgetPlanRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && BudgetPlan::where('project_id', $this->projectId)->where('periode', $this->periode)->exists()) {
                $validator->errors()->add('periode', 'A budget plan for this project and period already exists.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Budget plan created successfully.');

        $this->redirectRoute('budget-plans.index', navigate: true);
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
     * The master akuns available for selection.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::orderBy('kode_akun')->get();
    }

    /**
     * Total estimasi biaya from the rincian rows.
     */
    #[Computed]
    public function totalEstimasiBiaya(): float
    {
        return (float) array_sum(array_map(fn ($item) => (float) ($item['nominal'] ?? 0), $this->items));
    }

    /**
     * Suggested target laba based on pendapatan minus biaya.
     */
    #[Computed]
    public function targetLabaSaran(): float
    {
        return (float) $this->estimasiPendapatan - $this->totalEstimasiBiaya;
    }

    /**
     * Render the budget plan create page.
     */
    public function render()
    {
        return view('livewire.budget-plans.create');
    }
}
