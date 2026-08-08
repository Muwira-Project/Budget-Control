<?php

namespace App\Livewire\Realisasi;

use App\Http\Requests\Realisasi\StoreRealisasiRequest;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Kategori;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\RealisasiService;
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

    public ?int $kategoriId = null;

    public string $tanggal = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    public string $pihakJenis = 'vendor';

    /**
     * Set the default realisasi date.
     */
    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Store a newly created realisasi.
     */
    public function save(RealisasiService $service): void
    {
        $rules = (new StoreRealisasiRequest)->rules();

        $rules['akun_id'][] = function (string $attribute, mixed $value, $fail): void {
            if ($this->projectId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $value)
                    ->where('status', 'approved')
                    ->doesntExist()) {
                $fail('Account must be allocated to the selected project.');
            }
        };

        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'akun_id' => $this->akunId,
                'vendor_id' => $this->vendorId,
                'supplier_id' => $this->supplierId,
                'mandor_id' => $this->mandorId,
                'investor_id' => $this->investorId,
                'kategori_id' => $this->kategoriId,
                'tanggal' => $this->tanggal,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            $rules,
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
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Actual added successfully.');

        $this->redirectRoute('realisasi.index', navigate: true);
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
     * The kategoris available for selection.
     */
    #[Computed]
    public function kategoris()
    {
        return Kategori::orderBy('nama')->get();
    }

    /**
     * Render the realisasi create page.
     */
    public function render()
    {
        return view('livewire.realisasi.create');
    }
}
