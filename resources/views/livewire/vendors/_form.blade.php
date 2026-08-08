<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="kode" :value="__('Vendor Code')" />
        <x-text-input id="kode" class="mt-1 block w-full" type="text" wire:model="kode" placeholder="e.g. VND-001" required autofocus />
        <x-input-error :messages="$errors->get('kode')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nama" :value="__('Vendor Name')" />
        <x-text-input id="nama" class="mt-1 block w-full" type="text" wire:model="nama" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="telepon" :value="__('Phone (optional)')" />
        <x-text-input id="telepon" class="mt-1 block w-full" type="text" wire:model="telepon" placeholder="e.g. 021-5551234" />
        <x-input-error :messages="$errors->get('telepon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="alamat" :value="__('Address (optional)')" />
        <x-text-input id="alamat" class="mt-1 block w-full" type="text" wire:model="alamat" placeholder="e.g. Jl. Sudirman No. 10, Jakarta" />
        <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
    </div>
</div>