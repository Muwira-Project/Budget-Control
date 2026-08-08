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

    <div>
        <x-input-label for="pihak_jenis" :value="__('Party Type')" />
        <div class="mt-1 flex gap-4">
            <label class="inline-flex items-center">
                <input type="radio" wire:model.live="pihakJenis" value="vendor" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="ms-2 text-sm text-gray-700">Vendor (Services)</span>
            </label>
            <label class="inline-flex items-center">
                <input type="radio" wire:model.live="pihakJenis" value="supplier" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="ms-2 text-sm text-gray-700">Supplier (Goods)</span>
            </label>
            <label class="inline-flex items-center">
                <input type="radio" wire:model.live="pihakJenis" value="mandor" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="ms-2 text-sm text-gray-700">Mandor</span>
            </label>
            <label class="inline-flex items-center">
                <input type="radio" wire:model.live="pihakJenis" value="investor" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span class="ms-2 text-sm text-gray-700">Investor</span>
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        @if ($pihakJenis === 'vendor')
            <div>
                <x-input-label for="vendor_id" :value="__('Vendor (Services)')" />
                <select id="vendor_id" wire:model="vendorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Select Vendor --</option>
                    @foreach ($this->vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->kode }} - {{ $vendor->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
            </div>
        @elseif ($pihakJenis === 'supplier')
            <div>
                <x-input-label for="supplier_id" :value="__('Supplier (Goods)')" />
                <select id="supplier_id" wire:model="supplierId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Select Supplier --</option>
                    @foreach ($this->suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->kode }} - {{ $supplier->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
            </div>
        @elseif ($pihakJenis === 'mandor')
            <div>
                <x-input-label for="mandor_id" :value="__('Mandor')" />
                <select id="mandor_id" wire:model="mandorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Select Mandor --</option>
                    @foreach ($this->mandors as $mandor)
                        <option value="{{ $mandor->id }}">{{ $mandor->kode }} - {{ $mandor->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('mandor_id')" class="mt-2" />
            </div>
        @elseif ($pihakJenis === 'investor')
            <div>
                <x-input-label for="investor_id" :value="__('Investor')" />
                <select id="investor_id" wire:model="investorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Select Investor --</option>
                    @foreach ($this->investors as $investor)
                        <option value="{{ $investor->id }}">{{ $investor->kode }} - {{ $investor->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('investor_id')" class="mt-2" />
            </div>
        @endif

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
