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

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="tanggal" :value="__('Date')" />
            <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" required />
            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="jatuh_tempo" :value="__('Due Date (optional)')" />
            <x-text-input id="jatuh_tempo" class="mt-1 block w-full" type="date" wire:model="jatuhTempo" />
            <x-input-error :messages="$errors->get('jatuh_tempo')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="nominal" :value="__('Amount (Rp)')" />
        <x-text-input id="nominal" class="mt-1 block w-full" type="text" wire:model="nominal" placeholder="e.g. 500000000" required />
        <p class="mt-1 text-xs text-gray-500">Defaults to the project contract value (tax included).</p>
        <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="keterangan" :value="__('Description (optional)')" />
        <textarea id="keterangan" wire:model="keterangan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
    </div>
</div>
