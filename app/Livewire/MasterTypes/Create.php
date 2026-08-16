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

    /** @var array<int, array{label: string, tipe: string, is_required: bool}> */
    public array $fields = [];

    /**
     * Add a custom field definition row.
     */
    public function addField(): void
    {
        $this->fields[] = ['label' => '', 'tipe' => 'text', 'is_required' => false];
    }

    /**
     * Remove a custom field definition row.
     */
    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    /**
     * Store the new master menu.
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

        $service->create($validated + ['fields' => $this->fields]);

        session()->flash('status', 'Master menu created. It now appears under the Master menu.');

        $this->redirectRoute('master-types.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.master-types.create');
    }
}
