<?php

namespace App\Livewire\BudgetPlans;

use App\Http\Requests\BudgetPlan\UpdateBudgetPlanRequest;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\Project;
use App\Services\BudgetPlanService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public BudgetPlan $budgetPlan;

    public ?int $projectId = null;

    public string $periode = '';

    public string $estimasiPendapatan = '';

    public string $targetLaba = '';

    public array $items = [];

    /**
     * Load the budget plan being edited.
     */
    public function mount(BudgetPlan $budgetPlan): void
    {
        $this->budgetPlan = $budgetPlan->load('items');
        $this->projectId = $budgetPlan->project_id;
        $this->periode = $budgetPlan->periode;
        $this->estimasiPendapatan = $budgetPlan->estimasi_pendapatan;
        $this->targetLaba = $budgetPlan->target_laba;
        $this->items = $budgetPlan->items->map(fn ($item) => [
            'akun_id' => (string) $item->akun_id,
            'nominal' => (string) $item->nominal,
            'tanggal_mulai' => $item->tanggal_mulai?->format('Y-m-d') ?? '',
            'tanggal_selesai' => $item->tanggal_selesai?->format('Y-m-d') ?? '',
        ])->all();
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
     * Update the budget plan.
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
            (new UpdateBudgetPlanRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && BudgetPlan::where('project_id', $this->projectId)
                    ->where('periode', $this->periode)
                    ->where('id', '!=', $this->budgetPlan->id)
                    ->exists()) {
                $validator->errors()->add('periode', 'A budget plan for this project and period already exists.');
            }
        });

        $validated = $validator->validate();

        $service->update($this->budgetPlan, $validated);

        session()->flash('status', 'Budget plan updated successfully.');

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
     * Render the budget plan edit page.
     */
    public function render()
    {
        return view('livewire.budget-plans.edit');
    }
}
