<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="kode_akun" :value="__('Account Code')" />
        <x-text-input id="kode_akun" class="mt-1 block w-full" type="text" wire:model="kodeAkun" placeholder="e.g. 4-100" required />
        <x-input-error :messages="$errors->get('kode_akun')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nama_akun" :value="__('Account Name')" />
        <x-text-input id="nama_akun" class="mt-1 block w-full" type="text" wire:model="namaAkun" placeholder="e.g. Income Maintenance" required />
        <x-input-error :messages="$errors->get('nama_akun')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="jenis_akun" :value="__('Account Type')" />
            <select id="jenis_akun" wire:model="jenisAkun" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="pendapatan">Income</option>
                <option value="pengeluaran">Expense</option>
            </select>
            <x-input-error :messages="$errors->get('jenis_akun')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kategori_id" :value="__('Classification (Category)')" />
            <select id="kategori_id" wire:model="kategoriId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Select Category --</option>
                @foreach ($this->kategoris as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->kode }} - {{ $kategori->nama }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kategori_id')" class="mt-2" />
        </div>
    </div>
</div>