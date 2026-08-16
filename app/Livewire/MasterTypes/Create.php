<?php

namespace App\Livewire\MasterTypes;

use App\Services\MasterTypeService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $kode = '';

    public string $nama = '';

    public ?string $deskripsi = null;

    public bool $flagAr = false;

    public bool $flagAp = false;

    public bool $aktif = true;

    /**
     * Store the new master type.
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
            'kode' => ['required', 'string', 'max:50', 'unique:master_types,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'flag_ar' => ['boolean'],
            'flag_ap' => ['boolean'],
            'aktif' => ['boolean'],
        ])->validate();

        $service->create($validated);

        session()->flash('status', 'Master type created.');

        $this->redirectRoute('master-types.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.master-types.create');
    }
}