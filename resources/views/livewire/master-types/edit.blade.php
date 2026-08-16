<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Edit Master Type') }}</h2>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="kode" :value="__('Code')" />
                    <x-text-input id="kode" class="mt-1 block w-full" wire:model="kode" />
                    <x-input-error :messages="$errors->get('kode')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nama" :value="__('Name')" />
                    <x-text-input id="nama" class="mt-1 block w-full" wire:model="nama" />
                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="flagAr" class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                        Sumber AR (pemasukan)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="flagAp" class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                        Sumber AP (pengeluaran)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="aktif" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        Active
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="deskripsi" :value="__('Description')" />
                    <textarea id="deskripsi" wire:model="deskripsi" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Save</x-primary-button>
                <a href="{{ route('master-types.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</div>