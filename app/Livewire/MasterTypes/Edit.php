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

    /** @var array<int, array{label: string, tipe: string, is_required: bool}> */
    public array $fields = [];

    public function mount(MasterType $masterType): void
    {
        $this->masterType = $masterType;
        $this->kode = $masterType->kode;
        $this->nama = $masterType->nama;
        $this->deskripsi = $masterType->deskripsi;
        $this->flagAr = (bool) $masterType->flag_ar;
        $this->flagAp = (bool) $masterType->flag_ap;
        $this->aktif = (bool) $masterType->aktif;
        $this->fields = $masterType->fields
            ->map(fn ($field) => [
                'label' => $field->label,
                'tipe' => $field->tipe,
                'is_required' => (bool) $field->is_required,
            ])
            ->values()
            ->all();
    }

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
     * Update the master menu.
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

        $service->update($this->masterType, $validated + ['fields' => $this->fields]);

        session()->flash('status', 'Master menu updated.');

        $this->redirectRoute('master-types.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.master-types.edit');
    }
}
