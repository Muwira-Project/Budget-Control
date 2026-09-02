<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Add Master Menu') }}</h2>
        <p class="mt-1 text-sm text-gray-500">Buat sub-menu baru di bawah menu Master (mis. PIC). Setiap menu punya kolom sendiri.</p>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="kode" :value="__('Code')" />
                    <x-text-input id="kode" class="mt-1 block w-full" wire:model="kode" placeholder="e.g. PIC" />
                    <x-input-error :messages="$errors->get('kode')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nama" :value="__('Name (menu label)')" />
                    <x-text-input id="nama" class="mt-1 block w-full" wire:model="nama" placeholder="e.g. PIC" />
                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="aktif" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        Active (tampil di sidebar)
                    </label>
                </div>
                <div class="sm:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="flagAr" class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                        AR (Piutang) — aktifkan filter AR/AP per item pada menu ini
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="flagAp" class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                        AP (Hutang) — aktifkan filter AR/AP per item pada menu ini
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="deskripsi" :value="__('Description')" />
                    <textarea id="deskripsi" wire:model="deskripsi" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between">
                        <x-input-label :value="__('Custom Fields')" />
                        <button type="button" wire:click="addField" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100">+ Add Field</button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Selain kolom Kode &amp; Nama, data tiap menu master bisa punya kolom tambahan sendiri.</p>

                    @if (count($fields) === 0)
                        <p class="mt-3 rounded-lg bg-gray-50 p-4 text-sm text-gray-500">Belum ada field tambahan.</p>
                    @else
                        <div class="mt-3 space-y-3">
                            @foreach ($fields as $index => $field)
                                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <div class="min-w-[180px] flex-1">
                                        <x-text-input class="block w-full" wire:model="fields.{{ $index }}.label" placeholder="Field label (mis. Telepon)" />
                                    </div>
                                    <select wire:model="fields.{{ $index }}.tipe" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="text">Text</option>
                                        <option value="textarea">Long Text</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                    </select>
                                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                        <input type="checkbox" wire:model="fields.{{ $index }}.is_required" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                        Required
                                    </label>
                                    <button type="button" wire:click="removeField({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                        <x-icon name="x-mark" class="h-4 w-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Save</x-primary-button>
                <a href="{{ route('master-types.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</div>