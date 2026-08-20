<?php

namespace App\Livewire\MasterItems;

use App\Models\MasterItem;
use App\Services\MasterItemService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public MasterItem $masterItem;

    public string $kode = '';

    public string $nama = '';

    public bool $aktif = true;

    public bool $flagAr = false;

    public bool $flagAp = false;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(MasterItem $masterItem): void
    {
        $masterItem->load('masterType.fields');

        $this->masterItem = $masterItem;
        $this->kode = $masterItem->kode;
        $this->nama = $masterItem->nama;
        $this->aktif = (bool) $masterItem->aktif;
        $this->flagAr = (bool) $masterItem->flag_ar;
        $this->flagAp = (bool) $masterItem->flag_ap;
        $this->data = $masterItem->data ?? [];
    }

    /**
     * Update the master item with its custom field values.
     */
    public function save(MasterItemService $service): void
    {
        $rules = [
            'kode' => ['required', 'string', 'max:50', 'unique:master_items,kode,'.$this->masterItem->id.',id,master_type_id,'.$this->masterItem->master_type_id],
            'nama' => ['required', 'string', 'max:255'],
            'aktif' => ['boolean'],
        ];

        $payload = ['data' => $this->data];

        foreach ($this->masterItem->masterType->fields as $field) {
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

        $service->update($this->masterItem, [
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'aktif' => $validated['aktif'],
            'data' => $payload['data'],
            'flag_ar' => $this->flagAr ? true : null,
            'flag_ap' => $this->flagAp ? true : null,
        ]);

        session()->flash('status', 'Master item updated.');

        $this->redirectRoute('master-items.index', ['masterType' => $this->masterItem->master_type_id], navigate: true);
    }

    public function render()
    {
        return view('livewire.master-items.edit');
    }
}
