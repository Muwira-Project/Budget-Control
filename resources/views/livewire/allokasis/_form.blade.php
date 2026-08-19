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

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="budget" :value="__('Budget (Rp)')" />
            <x-text-input id="budget" class="mt-1 block w-full" type="text" wire:model="budget" placeholder="e.g. 250000000" required />
            <p class="mt-1 text-xs text-gray-500">Filled automatically from the project Budget Plan when available (can still be edited).</p>
            <x-input-error :messages="$errors->get('budget')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="allocation" :value="__('Allocation (Rp)')" />
            <x-text-input id="allocation" class="mt-1 block w-full" type="text" wire:model="allocationNominal" placeholder="e.g. 200000000" required />
            <p class="mt-1 text-xs text-gray-500">Allocation must not exceed the budget. New allocations are created as drafts and await admin approval.</p>
            <x-input-error :messages="$errors->get('allocation')" class="mt-2" />
        </div>
    </div>
</div>