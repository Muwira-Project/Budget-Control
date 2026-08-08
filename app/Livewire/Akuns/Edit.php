<?php

namespace App\Livewire\Akuns;

use App\Http\Requests\Akun\UpdateAkunRequest;
use App\Models\Akun;
use App\Models\Kategori;
use App\Services\AkunService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Akun $akun;

    public string $kodeAkun = '';

    public string $namaAkun = '';

    public string $jenisAkun = 'pengeluaran';

    public ?int $kategoriId = null;

    /**
     * Load the akun being edited.
     */
    public function mount(Akun $akun): void
    {
        $this->akun = $akun;
        $this->kodeAkun = $akun->kode_akun;
        $this->namaAkun = $akun->nama_akun;
        $this->jenisAkun = $akun->jenis_akun->value;
        $this->kategoriId = $akun->kategori_id;
    }

    /**
     * Update the akun.
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
            (new UpdateAkunRequest)->rules($this->akun->id),
        )->validate();

        $service->update($this->akun, $validated);

        session()->flash('status', 'Account updated successfully.');

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
     * Render the akun edit page.
     */
    public function render()
    {
        return view('livewire.akuns.edit');
    }
}
