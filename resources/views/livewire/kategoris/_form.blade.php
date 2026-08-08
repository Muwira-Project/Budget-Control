<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="kode" :value="__('Category Code')" />
        <x-text-input id="kode" class="mt-1 block w-full" type="text" wire:model="kode" placeholder="e.g. KAT-001" required autofocus />
        <x-input-error :messages="$errors->get('kode')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nama" :value="__('Category Name')" />
        <x-text-input id="nama" class="mt-1 block w-full" type="text" wire:model="nama" placeholder="e.g. Material" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
    </div>
</div>