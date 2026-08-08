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
            <x-input-label for="periode" :value="__('Period (YYYY-MM)')" />
            <x-text-input id="periode" class="mt-1 block w-full" type="month" wire:model="periode" required />
            <x-input-error :messages="$errors->get('periode')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="estimasi_pendapatan" :value="__('Estimated Income (Rp)')" />
            <x-text-input id="estimasi_pendapatan" class="mt-1 block w-full" type="text" wire:model.live="estimasiPendapatan" placeholder="e.g. 500000000" required />
            <x-input-error :messages="$errors->get('estimasi_pendapatan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="target_laba" :value="__('Target Profit (Rp)')" />
            <x-text-input id="target_laba" class="mt-1 block w-full" type="text" wire:model="targetLaba" placeholder="e.g. 200000000" />
            <p class="mt-1 text-xs text-gray-500">Saran: {{ format_idr($this->targetLabaSaran) }}</p>
            <x-input-error :messages="$errors->get('target_laba')" class="mt-2" />
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between">
            <x-input-label :value="__('Budget Details per Account')" />
            <button type="button" wire:click="addItem" class="rounded-md border border-blue-600 px-3 py-1.5 text-sm font-semibold text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Account
            </button>
        </div>

        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3 w-48">Start Date</th>
                        <th class="px-4 py-3 w-48">End Date</th>
                        <th class="px-4 py-3 w-56">Amount (Rp)</th>
                        <th class="px-4 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="px-4 py-2">
                                <select wire:model="items.{{ $index }}.akun_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">-- Select Account --</option>
                                    @foreach ($this->akuns as $akun)
                                        <option value="{{ $akun->id }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('items.'.$index.'.akun_id')" class="mt-1" />
                            </td>
                            <td class="px-4 py-2">
                                <x-text-input class="block w-full" type="date" wire:model="items.{{ $index }}.tanggal_mulai" />
                                <x-input-error :messages="$errors->get('items.'.$index.'.tanggal_mulai')" class="mt-1" />
                            </td>
                            <td class="px-4 py-2">
                                <x-text-input class="block w-full" type="date" wire:model="items.{{ $index }}.tanggal_selesai" />
                                <x-input-error :messages="$errors->get('items.'.$index.'.tanggal_selesai')" class="mt-1" />
                            </td>
                            <td class="px-4 py-2">
                                <x-text-input class="block w-full" type="text" wire:model="items.{{ $index }}.nominal" placeholder="e.g. 25000000" required />
                                <x-input-error :messages="$errors->get('items.'.$index.'.nominal')" class="mt-1" />
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" wire:click="removeItem({{ $index }})" class="rounded-md text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-40" @if (count($items) === 1) disabled @endif>Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-sm text-gray-500">No details yet. Click "+ Add Account" to add the first account.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-end gap-4 text-sm">
            <span class="text-gray-500">Total Estimated Biaya:</span>
            <span class="font-semibold text-gray-900">{{ format_idr($this->totalEstimasiBiaya) }}</span>
        </div>

        <x-input-error :messages="$errors->get('items')" class="mt-2" />
    </div>
</div>
