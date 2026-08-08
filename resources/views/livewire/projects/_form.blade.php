<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="kode" :value="__('Project Code')" />
        <x-text-input id="kode" class="mt-1 block w-full" type="text" wire:model="kode" placeholder="e.g. PRJ-2026-001" required autofocus />
        <x-input-error :messages="$errors->get('kode')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nama" :value="__('Project Name')" />
        <x-text-input id="nama" class="mt-1 block w-full" type="text" wire:model="nama" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="lokasi" :value="__('Location (optional)')" />
        <x-text-input id="lokasi" class="mt-1 block w-full" type="text" wire:model="lokasi" placeholder="e.g. Jakarta Selatan" />
        <x-input-error :messages="$errors->get('lokasi')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="jenis" :value="__('Project Type')" />
        <select id="jenis" wire:model.live="jenis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="barang">Goods (PPN 11%)</option>
            <option value="jasa">Services (Tax 2%)</option>
        </select>
        <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="qty" :value="__('Qty (optional)')" />
            <x-text-input id="qty" class="mt-1 block w-full" type="number" step="0.01" min="0" wire:model="qty" placeholder="e.g. 500" />
            <x-input-error :messages="$errors->get('qty')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="satuan" :value="__('Unit (optional)')" />
            <x-text-input id="satuan" class="mt-1 block w-full" type="text" wire:model="satuan" placeholder="e.g. unit, m2, lot" />
            <x-input-error :messages="$errors->get('satuan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="harga_satuan" :value="__('Unit Price (optional)')" />
            <x-text-input id="harga_satuan" class="mt-1 block w-full" type="number" step="0.01" min="0" wire:model="hargaSatuan" placeholder="e.g. 1500000" />
            <x-input-error :messages="$errors->get('harga_satuan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="pajak" :value="__('Tax (%) (optional)')" />
            <x-text-input id="pajak" class="mt-1 block w-full" type="number" step="0.01" min="0" max="100" wire:model="pajak" placeholder="Automatic: 11% goods, 2% services" />
            <x-input-error :messages="$errors->get('pajak')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="tanggal_mulai" :value="__('Start Date (optional)')" />
            <x-text-input id="tanggal_mulai" class="mt-1 block w-full" type="date" wire:model="tanggalMulai" />
            <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="target_selesai" :value="__('Target Completion (optional)')" />
            <x-text-input id="target_selesai" class="mt-1 block w-full" type="date" wire:model="targetSelesai" />
            <x-input-error :messages="$errors->get('target_selesai')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="active">Active</option>
            <option value="completed">Completed</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>