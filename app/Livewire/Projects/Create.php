<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectJenis;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\MasterItem;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public ?string $lokasi = null;

    public ?string $pic = null;

    public ?int $projectCategoryId = null;

    public ?string $subWork = null;

    public ?string $periode = null;

    public string $jenis = 'barang';

    public ?string $qty = null;

    public ?string $satuan = null;

    public ?string $hargaSatuan = null;

    public ?string $pajak = '11';

    public ?string $tanggalMulai = null;

    public ?string $targetSelesai = null;

    public string $status = 'progress';

    /**
     * Keep the tax rate in sync with the project type (11% PPN for goods, 2% for services).
     */
    public function updatedJenis(): void
    {
        $this->pajak = (string) ProjectJenis::from($this->jenis)->pajakDefault();
    }

    /**
     * Store a newly created project.
     */
    public function save(ProjectService $service): void
    {
        $validated = Validator::make(
            [
                'kode' => $this->kode,
                'nama' => $this->nama,
                'lokasi' => $this->lokasi,
                'pic' => $this->pic,
                'project_category_id' => $this->projectCategoryId,
                'sub_work' => $this->subWork,
                'periode' => $this->periode,
                'jenis' => $this->jenis,
                'qty' => $this->qty !== null && $this->qty !== '' ? $this->qty : null,
                'satuan' => $this->satuan,
                'harga_satuan' => $this->hargaSatuan !== null && $this->hargaSatuan !== '' ? $this->hargaSatuan : null,
                'pajak' => $this->pajak !== null && $this->pajak !== '' ? $this->pajak : 0,
                'tanggal_mulai' => $this->tanggalMulai !== null && $this->tanggalMulai !== '' ? $this->tanggalMulai : null,
                'target_selesai' => $this->targetSelesai !== null && $this->targetSelesai !== '' ? $this->targetSelesai : null,
                'status' => $this->status,
            ],
            (new StoreProjectRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', 'Project created successfully.');

        $this->redirectRoute('projects.index', navigate: true);
    }

    /**
     * The project categories available for the form (dynamic master).
     */
    public function projectCategories()
    {
        return MasterItem::query()
            ->whereHas('masterType', fn ($query) => $query->where('kode', 'PROJECT_CATEGORY')->where('aktif', true))
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();
    }

    /**
     * Render the project create page.
     */
    public function render()
    {
        return view('livewire.projects.create');
    }
}
