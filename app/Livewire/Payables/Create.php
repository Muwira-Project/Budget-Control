<?php

namespace App\Livewire\Payables;

use App\Http\Requests\Payable\StorePayableRequest;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\PayableService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public ?int $akunId = null;

    public ?int $vendorId = null;

    public ?int $supplierId = null;

    public ?int $mandorId = null;

    public ?int $investorId = null;

    public string $pihakJenis = 'vendor';

    public string $tanggal = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public string $jenisPajak = '';

    public bool $pajakInclude = true;

    public ?string $keterangan = null;

    /**
     * Set the default payable date.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Store a newly created payable.
     */
    public function save(PayableService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'akun_id' => $this->akunId,
                'vendor_id' => $this->vendorId,
                'supplier_id' => $this->supplierId,
                'mandor_id' => $this->mandorId,
                'investor_id' => $this->investorId,
                'tanggal' => $this->tanggal,
                'jatuh_tempo' => $this->jatuhTempo !== '' ? $this->jatuhTempo : null,
                'nominal' => $this->nominal,
                'jenis_pajak' => $this->jenisPajak !== '' ? $this->jenisPajak : null,
                'pajak_include' => $this->pajakInclude,
                'keterangan' => $this->keterangan,
            ],
            (new StorePayableRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            $partyCount = collect([
                $this->vendorId,
                $this->supplierId,
                $this->mandorId,
                $this->investorId,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 1) {
                $validator->errors()->add('vendor_id', 'Select exactly one party.');
            }

            if (count(array_filter([$this->vendorId, $this->supplierId])) > 1) {
                $validator->errors()->add('vendor_id', 'Only one can be selected: Vendor or Supplier.');
            }

            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $this->akunId)
                    ->where('status', 'approved')
                    ->doesntExist()) {
                $validator->errors()->add('akun_id', 'Account must be allocated (approved) to the selected project.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Payable created successfully.');

        $this->redirectRoute('payables.index', navigate: true);
    }

    /**
     * Reset the party selection when the party type changes.
     */
    public function updatedPihakJenis(): void
    {
        $this->vendorId = null;
        $this->supplierId = null;
        $this->mandorId = null;
        $this->investorId = null;
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
     * The approved allocations for the selected project.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::query()
            ->when($this->projectId, fn ($query) => $query->whereIn('id', ProjectAkun::where('project_id', $this->projectId)->where('status', 'approved')->pluck('akun_id')))
            ->orderBy('kode_akun')
            ->get();
    }

    /**
     * The vendors available for selection.
     */
    #[Computed]
    public function vendors()
    {
        return Vendor::orderBy('nama')->get();
    }

    /**
     * The suppliers available for selection.
     */
    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('nama')->get();
    }

    /**
     * The mandors available for selection.
     */
    #[Computed]
    public function mandors()
    {
        return Mandor::orderBy('nama')->get();
    }

    /**
     * The investors available for selection.
     */
    #[Computed]
    public function investors()
    {
        return Investor::orderBy('nama')->get();
    }

    /**
     * Render the payable create page.
     */
    public function render()
    {
        return view('livewire.payables.create');
    }
}
