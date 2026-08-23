<?php

namespace App\Livewire\Allokasis;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Http\Requests\ProjectAkun\StoreProjectAkunRequest;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Receivable;
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

    public string $type = 'other_outcome'; // ap, ar, other_income, other_outcome

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    public ?int $payableId = null;

    public ?int $receivableId = null;

    public string $customName = '';

    /**
     * Mount with optional project_id parameter (null or 'non-project' for non-project).
     */
    public function mount(?string $project_id = null): void
    {
        if ($project_id !== null && $project_id !== 'non-project') {
            $this->projectId = (int) $project_id;
        }
    }

    /**
     * Reset dependent fields when project changes.
     */
    public function updatedProjectId(): void
    {
        $this->akunId = null;
        $this->budget = '';
        $this->allocationNominal = '';
        $this->pihakTypeId = null;
        $this->pihakItemId = null;
        $this->payableId = null;
        $this->receivableId = null;
        $this->customName = '';
    }

    /**
     * Reset party selection when type changes.
     */
    public function updatedType(): void
    {
        $this->pihakTypeId = null;
        $this->pihakItemId = null;
        $this->payableId = null;
        $this->receivableId = null;
        $this->customName = '';
        $this->allocationNominal = '';
    }

    /**
     * Reset party item when party type changes.
     */
    public function updatedPihakTypeId(): void
    {
        $this->pihakItemId = null;
        $this->payableId = null;
        $this->receivableId = null;
        $this->allocationNominal = '';
    }

    /**
     * Prefill allocation with outstanding balance when payable/receivable selected.
     */
    public function updatedPayableId(): void
    {
        if ($this->payableId) {
            $payable = Payable::find($this->payableId);
            if ($payable) {
                $this->pihakTypeId = $payable->pihak_type_id;
                $this->pihakItemId = $payable->pihak_item_id;
                $this->allocationNominal = (string) $payable->sisa;
            }
        }
    }

    public function updatedReceivableId(): void
    {
        if ($this->receivableId) {
            $receivable = Receivable::find($this->receivableId);
            if ($receivable) {
                $this->pihakTypeId = $receivable->pihak_type_id;
                $this->pihakItemId = $receivable->pihak_item_id;
                $this->allocationNominal = (string) $receivable->sisa;
            }
        }
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
            if ($this->type === 'other_outcome' || $this->type === 'other_income') {
                $this->allocationNominal = (string) $item->nominal;
            }
        }
    }

    /**
     * Outstanding balance for selected party item (AP/AR) - fallback.
     */
    #[Computed]
    public function outstandingBalance(): float
    {
        if (! $this->pihakItemId || ! in_array($this->type, ['ap', 'ar'])) {
            return 0.0;
        }

        return app(OutstandingBalanceService::class)->calculate($this->type, $this->pihakItemId);
    }

    /**
     * Party types available for AP/AR (MasterType with flag_ap/flag_ar).
     */
    #[Computed]
    public function partyTypes()
    {
        if (! in_array($this->type, ['ap', 'ar'])) {
            return collect();
        }

        $flag = $this->type === 'ap' ? 'flag_ap' : 'flag_ar';

        return MasterType::where('aktif', true)
            ->where($flag, true)
            ->where('is_system', true)
            ->orderBy('sort')
            ->get();
    }

    /**
     * Party items filtered by selected party type and AP/AR flag.
     */
    #[Computed]
    public function partyItems()
    {
        if (! $this->pihakTypeId || ! in_array($this->type, ['ap', 'ar'])) {
            return collect();
        }

        $flag = $this->type === 'ap' ? 'flag_ap' : 'flag_ar';

        return MasterItem::where('master_type_id', $this->pihakTypeId)
            ->where('aktif', true)
            ->where($flag, true)
            ->orderBy('nama')
            ->get();
    }

    /**
     * Payables available for AP type (unpaid/partial).
     */
    #[Computed]
    public function availablePayables()
    {
        if ($this->type !== 'ap') {
            return collect();
        }

        return Payable::with(['pihakItem', 'akun'])
            ->where('status', '!=', PayableStatus::Lunas)
            ->whereRaw('nominal - nominal_dibayar > 0')
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Receivables available for AR type (unpaid/partial).
     */
    #[Computed]
    public function availableReceivables()
    {
        if ($this->type !== 'ar') {
            return collect();
        }

        return Receivable::with(['pihakItem'])
            ->where('status', '!=', ReceivableStatus::Lunas)
            ->whereRaw('nominal - nominal_dibayar > 0')
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Store a newly created allocation draft.
     */
    public function save(ProjectAkunService $service): void
    {
        $data = [
            'project_id' => $this->projectId,
            'akun_id' => $this->akunId,
            'budget' => $this->budget,
            'allocation' => $this->allocationNominal,
            'type' => $this->type,
        ];

        // Add party fields for AP/AR
        if (in_array($this->type, ['ap', 'ar'])) {
            $data['pihak_type_id'] = $this->pihakTypeId;
            $data['pihak_item_id'] = $this->pihakItemId;
            $data['outstanding_balance'] = $this->outstandingBalance;

            if ($this->type === 'ap' && $this->payableId) {
                $data['payable_id'] = $this->payableId;
            }
            if ($this->type === 'ar' && $this->receivableId) {
                $data['receivable_id'] = $this->receivableId;
            }
        }

        // Add custom name for Other Income/Outcome
        if (in_array($this->type, ['other_income', 'other_outcome'])) {
            $data['custom_name'] = $this->customName;
        }

        $validator = Validator::make($data, (new StoreProjectAkunRequest)->rules());

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)->where('akun_id', $this->akunId)->exists()) {
                $validator->errors()->add('akun_id', 'This account is already allocated to this project.');
            }

            // Non-project: allow multiple rows per akun, but validate required fields per type
            if ($this->projectId === null) {
                if (in_array($this->type, ['ap', 'ar'])) {
                    if ($this->type === 'ap' && ! $this->payableId) {
                        $validator->errors()->add('payable_id', 'Please select a Payable record.');
                    }
                    if ($this->type === 'ar' && ! $this->receivableId) {
                        $validator->errors()->add('receivable_id', 'Please select a Receivable record.');
                    }
                }
                if (in_array($this->type, ['other_income', 'other_outcome']) && trim($this->customName) === '') {
                    $validator->errors()->add('custom_name', 'Custom name is required for Other Income/Outcome type.');
                }
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Allocation draft created successfully.');

        $this->redirectRoute('budgeting.index', navigate: true);
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
     * Type options for selection.
     */
    #[Computed]
    public function typeOptions(): array
    {
        return [
            'ap' => 'AP (Hutang)',
            'ar' => 'AR (Piutang)',
            'other_income' => 'Other Income',
            'other_outcome' => 'Other Outcome',
        ];
    }

    /**
     * Render the allocation create page.
     */
    public function render()
    {
        return view('livewire.allokasis.create');
    }
}
