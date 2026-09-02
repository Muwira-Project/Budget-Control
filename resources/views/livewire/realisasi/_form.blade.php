<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="project_id" :value="__('Project')" />
        <select id="project_id" wire:model.live="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">-- Select Project --</option>
            @foreach ($this->projects as $project)
                <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="akun_id" :value="__('Account Item')" />
        <select id="akun_id" wire:model="akunId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required {{ $this->akuns->isEmpty() ? 'disabled' : '' }}>
            <option value="">-- Select Item Account --</option>
            @foreach ($this->akuns as $akun)
                <option value="{{ $akun->id }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('akun_id')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="pihak_type_id" :value="__('Party Type (optional)' )" />
                <select id="pihak_type_id" wire:model.live="pihakTypeId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- No Party --</option>
                    @foreach ($this->partyTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('pihak_type_id')" class="mt-2" />
            </div>

            @if ($this->pihakTypeId !== null)
                <div>
                    <x-input-label for="pihak_item_id" :value="__('Party' )" />
                    <select id="pihak_item_id" wire:model="pihakItemId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Select Party --</option>
                        @foreach ($this->partyItems as $item)
                            <option value="{{ $item->id }}">{{ $item->kode }} - {{ $item->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pihak_item_id')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">Hanya menampilkan item dengan flag AP (hutang) atau netral.</p>
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="kategori_id" :value="__('Category (optional)')" />
            <select id="kategori_id" wire:model="kategoriId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Select Category --</option>
                @foreach ($this->kategoris as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->kode }} - {{ $kategori->nama }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kategori_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="tanggal" :value="__('Date')" />
            <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" required />
            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nominal" :value="__('Amount (Rp)')" />
            <x-text-input id="nominal" class="mt-1 block w-full" type="text" wire:model="nominal" placeholder="e.g. 25000000" required />
            <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="keterangan" :value="__('Description (optional)')" />
        <textarea id="keterangan" wire:model="keterangan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Transaction details"></textarea>
        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
    </div>
</div>
