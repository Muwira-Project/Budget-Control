<?php

namespace App\Livewire\MasterTypes;

use App\Models\MasterType;
use App\Services\MasterTypeService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public MasterType $masterType;

    public string $kode = '';

    public string $nama = '';

    public ?string $deskripsi = null;

    public bool $flagAr = false;

    public bool $flagAp = false;

    public bool $aktif = true;

    public function mount(MasterType $masterType): void
    {
        $this->masterType = $masterType;
        $this->kode = $masterType->kode;
        $this->nama = $masterType->nama;
        $this->deskripsi = $masterType->deskripsi;
        $this->flagAr = (bool) $masterType->flag_ar;
        $this->flagAp = (bool) $masterType->flag_ap;
        $this->aktif = (bool) $masterType->aktif;
    }

    /**
     * Update the master type.
     */
    public function save(MasterTypeService $service): void
    {
        $validated = Validator::make([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'flag_ar' => $this->flagAr,
            'flag_ap' => $this->flagAp,
            'aktif' => $this->aktif,
        ], [
            'kode' => ['required', 'string', 'max:50', 'unique:master_types,kode,'.$this->masterType->id],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'flag_ar' => ['boolean'],
            'flag_ap' => ['boolean'],
            'aktif' => ['boolean'],
        ])->validate();

        $service->update($this->masterType, $validated);

        session()->flash('status', 'Master type updated.');

        $this->redirectRoute('master-types.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.master-types.edit');
    }
}
