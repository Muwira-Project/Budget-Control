<?php

namespace App\Livewire\Akuns;

use App\Http\Requests\Akun\StoreAkunRequest;
use App\Models\Kategori;
use App\Services\AkunService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kodeAkun = '';

    public string $namaAkun = '';

    public string $jenisAkun = 'pengeluaran';

    public ?int $kategoriId = null;

    /**
     * Store a newly created akun.
     */
    public function save(AkunService $service): void
    {
        $validated = Validator::make(
            [
                'kode_akun' => $this->kodeAkun,
                'nama_akun' => $this->namaAkun,
                'jenis_akun' => $this->jenisAkun,
                'kategori_id' => $this->kategoriId,
            ],
            (new StoreAkunRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', 'Account created successfully.');

        $this->redirectRoute('akuns.index', navigate: true);
    }

    /**
     * The klasifikasi kategoris available for selection.
     */
    #[Computed]
    public function kategoris()
    {
        return Kategori::orderBy('nama')->get();
    }

    /**
     * Render the akun create page.
     */
    public function render()
    {
        return view('livewire.akuns.create');
    }
}
