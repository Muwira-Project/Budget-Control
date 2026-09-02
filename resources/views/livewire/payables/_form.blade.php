<div class="mt-6 grid grid-cols-1 gap-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
    </div>

    @if ($this->budgetInfo)
        <div class="rounded-lg border px-4 py-3 text-sm {{ $this->budgetInfo['over'] ? 'border-red-300 bg-red-50 text-red-800' : 'border-blue-200 bg-blue-50 text-blue-800' }}">
            <p class="font-semibold">Budget context for this account</p>
            <p class="mt-1">
                Allocation: <strong>Rp {{ number_format((float) $this->budgetInfo['allocation'], 0, ',', '.') }}</strong>
                &middot; Realized: <strong>Rp {{ number_format((float) $this->budgetInfo['realized'], 0, ',', '.') }}</strong>
                &middot; Remaining: <strong>Rp {{ number_format((float) $this->budgetInfo['remaining'], 0, ',', '.') }}</strong>
            </p>
            @if ($this->budgetInfo['over'])
                <p class="mt-1 font-semibold">Warning: this account is already over its approved allocation.</p>
            @endif
        </div>
    @endif

    <div>
        <x-input-label for="pihak_type_id" :value="__('Party Type')" />
        <select id="pihak_type_id" wire:model.live="pihakTypeId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Select Party Type --</option>
            @foreach ($this->partyTypes as $type)
                <option value="{{ $type->id }}">{{ $type->nama }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('pihak_type_id')" class="mt-2" />
    </div>

        @if ($this->pihakTypeId !== null)
            <div>
                <x-input-label for="pihak_item_id" :value="__('Party (Vendor / Supplier / Mandor / Investor)' )" />
                <select id="pihak_item_id" wire:model="pihakItemId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Select Party --</option>
                    @foreach ($this->partyItems as $item)
                        <option value="{{ $item->id }}">{{ $item->kode }} - {{ $item->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('pihak_item_id')" class="mt-2" />
                <p class="mt-1 text-xs text-gray-500">Hanya menampilkan item dengan flag AP (dikelompokkan sebagai hutang) atau netral.</p>
            </div>
        @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="tanggal" :value="__('Date')" />
            <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" required />
            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nomor_invoice" :value="__('Invoice No. (optional)')" />
            <x-text-input id="nomor_invoice" class="mt-1 block w-full" type="text" wire:model="nomorInvoice" placeholder="e.g. INV-2026-001" />
            <x-input-error :messages="$errors->get('nomor_invoice')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="jatuh_tempo" :value="__('Due Date (optional)')" />
            <x-text-input id="jatuh_tempo" class="mt-1 block w-full" type="date" wire:model="jatuhTempo" />
            <x-input-error :messages="$errors->get('jatuh_tempo')" class="mt-2" />
        </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="nominal" :value="__('Amount (Rp)')" />
            <x-text-input id="nominal" class="mt-1 block w-full" type="text" wire:model="nominal" placeholder="e.g. 25000000" required />
            <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="jenis_pajak" :value="__('Tax Type (optional)')" />
            <select id="jenis_pajak" wire:model="jenisPajak" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">None</option>
                <option value="ppn">PPN</option>
                <option value="pph">PPh</option>
            </select>
            <x-input-error :messages="$errors->get('jenis_pajak')" class="mt-2" />
        </div>
    </div>

    <div>
        <label class="inline-flex items-center">
            <input type="checkbox" wire:model="pajakInclude" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            <span class="ms-2 text-sm text-gray-700">Amount includes tax</span>
        </label>
    </div>

    <div>
        <x-input-label for="keterangan" :value="__('Description (optional)')" />
        <textarea id="keterangan" wire:model="keterangan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
    </div>
</div>
