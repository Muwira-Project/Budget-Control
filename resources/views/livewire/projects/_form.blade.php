<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="kode" :value="__('Project Code')" />
        <x-text-input id="kode" class="mt-1 block w-full" type="text" wire:model="kode" placeholder="e.g. PRJ-2026-001" required autofocus />
        <x-input-error :messages="$errors->get('kode')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="submit_date" :value="__('Submit Date (optional)')" />
        <x-text-input id="submit_date" class="mt-1 block w-full" type="date" wire:model="submitDate" />
        <x-input-error :messages="$errors->get('submit_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="po_number" :value="__('PO Number')" />
        <x-text-input id="po_number" class="mt-1 block w-full" type="text" wire:model="poNumber" placeholder="e.g. PO-2026-001" required="false" />
        <x-input-error :messages="$errors->get('po_number')" class="mt-2" />
        <p class="mt-1 text-xs text-slate-500">Wajib diisi saat status = Done (untuk AR Billed)</p>
    </div>

    <div>
        <x-input-label for="nama" :value="__('Project Name')" />
        <x-text-input id="nama" class="mt-1 block w-full" type="text" wire:model="nama" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="lokasi" :value="__('Location (optional)')" />
            <x-text-input id="lokasi" class="mt-1 block w-full" type="text" wire:model="lokasi" placeholder="e.g. Jakarta" />
            <x-input-error :messages="$errors->get('lokasi')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="division_id" :value="__('Division (optional)')" />
            <select id="division_id" wire:model="divisionId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Division...</option>
                @foreach ($this->divisionOptions() as $division)
                    <option value="{{ $division->id }}">{{ $division->kode }} - {{ $division->nama }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('divisionId')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="pic" :value="__('PIC (optional)')" />
        <x-text-input id="pic" class="mt-1 block w-full" type="text" wire:model="pic" placeholder="e.g. Budi Santoso" />
        <x-input-error :messages="$errors->get('pic')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="periode" :value="__('Period (optional)')" />
        <x-text-input id="periode" class="mt-1 block w-full" type="text" wire:model="periode" placeholder="e.g. 2026" />
        <x-input-error :messages="$errors->get('periode')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="project_category_id" :value="__('Project Category (optional)')" />
        <select id="project_category_id" wire:model="projectCategoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select Category...</option>
            @foreach ($this->projectCategories() as $category)
                <option value="{{ $category->id }}">{{ $category->nama }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('project_category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sub_work" :value="__('Sub Work (optional)')" />
        <x-text-input id="sub_work" class="mt-1 block w-full" type="text" wire:model="subWork" placeholder="e.g. Pekerjaan pondasi" />
        <x-input-error :messages="$errors->get('sub_work')" class="mt-2" />
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
    </div>

    <div>
        <x-input-label for="harga_satuan" :value="__('Unit Price (optional)')" />
        <x-text-input id="harga_satuan" class="mt-1 block w-full" type="number" step="0.01" min="0" wire:model="hargaSatuan" placeholder="e.g. 1500000" />
        <x-input-error :messages="$errors->get('harga_satuan')" class="mt-2" />
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
            @php
                $isEdit = isset($this->project) && $this->project->exists;
                $currentStatus = $isEdit ? $this->project->status : \App\Enums\ProjectStatus::tryFrom($this->status);
                $allStatuses = \App\Enums\ProjectStatus::cases();
            @endphp
            @foreach ($allStatuses as $statusEnum)
                @php
                    if ($isEdit) {
                        $isCurrent = $currentStatus && $currentStatus->value === $statusEnum->value;
                        $disabled = $currentStatus && !$isCurrent && !$currentStatus->canTransitionTo($statusEnum);
                    } else {
                        // On create: Draft and allowed initial transitions (In Progress, Cancelled) are enabled
                        $disabled = $statusEnum !== \App\Enums\ProjectStatus::Draft 
                            && !\App\Enums\ProjectStatus::Draft->canTransitionTo($statusEnum);
                    }
                @endphp
                <option value="{{ $statusEnum->value }}" {{ $disabled ? 'disabled' : '' }}>
                    {{ $statusEnum->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    @if ($this->status === 'revisi')
    <div>
        <x-input-label for="revisi_reason" :value="__('Alasan Revisi')" />
        <textarea id="revisi_reason" wire:model="revisiReason" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Jelaskan alasan revisi project..."></textarea>
        <x-input-error :messages="$errors->get('revisiReason')" class="mt-2" />
        <p class="mt-1 text-xs text-slate-500">Wajib diisi saat status = Revisi</p>
    </div>
    @endif
</div>