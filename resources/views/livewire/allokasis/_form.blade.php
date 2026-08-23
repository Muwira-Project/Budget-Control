<div class="mt-6 grid grid-cols-1 gap-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="project_id" :value="__('Project')" />
            <select id="project_id" wire:model.live="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Non-Project (Operational) --</option>
                @foreach ($this->projects as $project)
                    <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Pilih proyek untuk alokasi per proyek, atau biarkan kosong untuk budget non-proyek (operasional).</p>
            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="type" :value="__('Type')" />
            <select id="type" wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                @foreach ($this->typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Pilih tipe alokasi non-proyek.</p>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>
    </div>

    <!-- Payable/Receivable Selection for AP/AR -->
    <div wire:key="type-{{ $this->type }}">
        @if (in_array($this->type, ['ap', 'ar']))
        <div>
            @if ($this->type === 'ap')
                <x-input-label for="payable_id" :value="__('Pilih Payable (Hutang)')" />
                <select id="payable_id" wire:model="payableId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required {{ $this->availablePayables->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Invoice Hutang --</option>
                    @foreach ($this->availablePayables as $p)
                        <option value="{{ $p->id }}"
                            data-party="{{ $p->pihakItem->nama ?? '-' }}"
                            data-nominal="{{ number_format($p->nominal, 0, ',', '.') }}"
                            data-sisa="{{ number_format($p->sisa, 0, ',', '.') }}">
                            {{ $p->nomor_invoice ?? 'INV-'.$p->id }} | {{ $p->tanggal->format('d/m/Y') }} | {{ $p->pihakItem->nama ?? '-' }} | Sisa: {{ number_format($p->sisa, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                @if ($this->availablePayables->isEmpty())
                    <p class="mt-1 text-xs text-gray-500">Tidak ada hutang (AP) yang belum lunas.</p>
                @endif
                <x-input-error :messages="$errors->get('payable_id')" class="mt-2" />
            @else
                <x-input-label for="receivable_id" :value="__('Pilih Receivable (Piutang)')" />
                <select id="receivable_id" wire:model="receivableId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required {{ $this->availableReceivables->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Invoice Piutang --</option>
                    @foreach ($this->availableReceivables as $r)
                        <option value="{{ $r->id }}"
                            data-party="{{ $r->pihakItem->nama ?? '-' }}"
                            data-nominal="{{ number_format($r->nominal, 0, ',', '.') }}"
                            data-sisa="{{ number_format($r->sisa, 0, ',', '.') }}">
                            {{ $r->nomor_invoice ?? 'INV-'.$r->id }} | {{ $r->tanggal->format('d/m/Y') }} | {{ $r->pihakItem->nama ?? '-' }} | Sisa: {{ number_format($r->sisa, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                @if ($this->availableReceivables->isEmpty())
                    <p class="mt-1 text-xs text-gray-500">Tidak ada piutang (AR) yang belum lunas.</p>
                @endif
                <x-input-error :messages="$errors->get('receivable_id')" class="mt-2" />
            @endif
        </div>
        @endif
    </div>

    <!-- Custom Name for Other Income/Outcome -->
    <div wire:key="type-{{ $this->type }}">
        @if (in_array($this->type, ['other_income', 'other_outcome']))
        <div>
            <x-input-label for="custom_name" :value="__('Nama')" />
            <x-text-input id="custom_name" class="mt-1 block w-full" type="text" wire:model="customName" placeholder="Masukkan nama (mis. Bunga Bank, Sewa Gudang, dll)" required />
            <x-input-error :messages="$errors->get('custom_name')" class="mt-2" />
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
            <x-input-label for="budget" :value="__('Budget (Rp)')" />
            <x-text-input id="budget" class="mt-1 block w-full" type="text" wire:model="budget" placeholder="e.g. 250000000" required />
            <p class="mt-1 text-xs text-gray-500">Filled automatically from the project Budget Plan when available (can still be edited).</p>
            <x-input-error :messages="$errors->get('budget')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="allocation" :value="__('Allocation (Rp)')" />
            <x-text-input id="allocation" class="mt-1 block w-full" type="text" wire:model="allocationNominal" placeholder="e.g. 200000000" required />
            @if (($this->payableId || $this->receivableId) && in_array($this->type, ['ap', 'ar']))
                <p class="mt-1 text-xs text-gray-500">Sisa invoice terpilih otomatis diisi (editable)</p>
            @elseif ($this->outstandingBalance > 0 && in_array($this->type, ['ap', 'ar']))
                <p class="mt-1 text-xs text-gray-500">Outstanding balance: {{ number_format($this->outstandingBalance, 0, ',', '.') }} (prefilled, editable)</p>
            @else
                <p class="mt-1 text-xs text-gray-500">Allocation must not exceed the budget. New allocations are created as drafts and await admin approval.</p>
            @endif
            <x-input-error :messages="$errors->get('allocation')" class="mt-2" />
        </div>
    </div>
</div>