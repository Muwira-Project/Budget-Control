<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Add Master Item') }}</h2>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="master_type_id" :value="__('Master Type')" />
                    <select id="master_type_id" wire:model="masterTypeId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select...</option>
                        @foreach (\App\Models\MasterType::orderBy('nama')->get() as $type)
                            <option value="{{ $type->id }}">{{ $type->kode }} - {{ $type->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('master_type_id')" class="mt-2" />
                </div>
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
                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="aktif" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        Active
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" :value="__('Description')" />
                    <textarea id="keterangan" wire:model="keterangan" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Save</x-primary-button>
                @if ($masterTypeId !== '')
                    <a href="{{ route('master-items.index', ['masterType' => $masterTypeId]) }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                @else
                    <a href="{{ route('master-types.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                @endif
            </div>
        </form>
    </div>
</div>