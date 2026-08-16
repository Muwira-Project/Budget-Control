<?php

namespace App\Livewire\MasterItems;

use App\Models\MasterType;
use App\Services\MasterItemService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public MasterType $masterType;

    public string $kode = '';

    public string $nama = '';

    public bool $aktif = true;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(MasterType $masterType): void
    {
        $masterType->load('fields');

        $this->masterType = $masterType;
    }

    /**
     * Store the new master item with its custom field values.
     */
    public function save(MasterItemService $service): void
    {
        $rules = [
            'kode' => ['required', 'string', 'max:50', 'unique:master_items,kode,NULL,id,master_type_id,'.$this->masterType->id],
            'nama' => ['required', 'string', 'max:255'],
            'aktif' => ['boolean'],
        ];

        $payload = ['data' => $this->data];

        foreach ($this->masterType->fields as $field) {
            $value = $this->data[(string) $field->id] ?? null;
            $payload['data'][(string) $field->id] = match ($field->tipe) {
                'number' => $value === null || $value === '' ? null : (float) $value,
                'date' => $value === null || $value === '' ? null : $value,
                default => $value === null ? null : (string) $value,
            };

            if ($field->is_required && ($payload['data'][(string) $field->id] === null || $payload['data'][(string) $field->id] === '')) {
                $rules['data.'.$field->id] = ['required'];
            }
        }

        $validated = Validator::make([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'aktif' => $this->aktif,
            'data' => $payload['data'],
        ], $rules)->validate();

        $service->create([
            'master_type_id' => $this->masterType->id,
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'aktif' => $validated['aktif'],
            'data' => $payload['data'],
        ]);

        session()->flash('status', 'Master item created.');

        $this->redirectRoute('master-items.index', ['masterType' => $this->masterType->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.master-items.create');
    }
}
