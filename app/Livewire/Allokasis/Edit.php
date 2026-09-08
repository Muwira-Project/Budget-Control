<?php

namespace App\Livewire\Allokasis;

use App\Enums\AllocationStatus;
use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Http\Requests\ProjectAkun\UpdateProjectAkunRequest;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Receivable;
use App\Services\ProjectAkunService;
use Illuminate\Support\Facades\DB;
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

    public string $type = 'other_outcome';

    public ?int $pihakTypeId = null;

    public ?int $pihakItemId = null;

    public ?int $payableId = null;

    public ?int $receivableId = null;

    public string $customName = '';

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
        $this->type = $projectAkun->type ?? 'other_outcome';
        $this->pihakTypeId = $projectAkun->pihak_type_id;
        $this->pihakItemId = $projectAkun->pihak_item_id;
        $this->payableId = $projectAkun->payable_id;
        $this->receivableId = $projectAkun->receivable_id;
        $this->customName = $projectAkun->custom_name ?? '';
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
     * Outstanding balance for selected party item (AP/AR) - fallback.
     */
    #[Computed]
    public function outstandingBalance(): float
    {
        if (! $this->pihakItemId || ! in_array($this->type, ['ap', 'ar'])) {
            return 0.0;
        }

        if ($this->type === 'ap') {
            return (float) Payable::where('pihak_item_id', $this->pihakItemId)
                ->whereRaw('nominal - nominal_dibayar > 0')
                ->sum(DB::raw('nominal - nominal_dibayar'));
        }

        // AR
        return (float) Receivable::where('pihak_item_id', $this->pihakItemId)
            ->whereRaw('nominal - nominal_dibayar > 0')
            ->sum(DB::raw('nominal - nominal_dibayar'));
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
            ->whereRaw('nominal - nominal_dibayar > 0')
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Update the allocation and move it back to draft.
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

        $validator = Validator::make($data, (new UpdateProjectAkunRequest)->rules());

        $validator->after(function ($validator): void {
            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $this->akunId)
                    ->where('id', '!=', $this->allocation->id)
                    ->exists()) {
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

        $service->update($this->allocation, $validated);

        $this->allocation->update(['status' => AllocationStatus::Draft]);

        session()->flash('status', 'Allocation updated successfully.');

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
     * Render the allocation edit page.
     */
    public function render()
    {
        return view('livewire.allokasis.edit');
    }
}
