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

    public string $masterTypeId = '';

    public string $kode = '';

    public string $nama = '';

    public ?string $keterangan = null;

    public bool $aktif = true;

    public function mount(MasterItem $masterItem): void
    {
        $this->masterItem = $masterItem;
        $this->masterTypeId = (string) $masterItem->master_type_id;
        $this->kode = $masterItem->kode;
        $this->nama = $masterItem->nama;
        $this->keterangan = $masterItem->keterangan;
        $this->aktif = (bool) $masterItem->aktif;
    }

    /**
     * Update the master item.
     */
    public function save(MasterItemService $service): void
    {
        $validated = Validator::make([
            'master_type_id' => $this->masterTypeId,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
            'aktif' => $this->aktif,
        ], [
            'master_type_id' => ['required', 'exists:master_types,id'],
            'kode' => ['required', 'string', 'max:50', 'unique:master_items,kode,'.$this->masterItem->id.',id,master_type_id,'.$this->masterTypeId],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['boolean'],
        ])->validate();

        $service->update($this->masterItem, $validated);

        session()->flash('status', 'Master item updated.');

        $this->redirectRoute('master-items.index', ['masterType' => $this->masterTypeId], navigate: true);
    }

    public function render()
    {
        return view('livewire.master-items.edit');
    }
}
