<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Edit') }} {{ $this->masterItem->masterType->nama }}</h2>

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

                @foreach ($this->masterItem->masterType->fields as $field)
                    <div class="{{ $field->tipe === 'textarea' ? 'sm:col-span-2' : '' }}">
                        <x-input-label :for="'field_' . $field->id" :value="$field->label . ($field->is_required ? ' *' : '')" />
                        @if ($field->tipe === 'textarea')
                            <textarea id="field_{{ $field->id }}" wire:model="data.{{ $field->id }}" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @elseif ($field->tipe === 'number')
                            <x-text-input id="field_{{ $field->id }}" class="mt-1 block w-full" type="number" step="0.01" wire:model="data.{{ $field->id }}" />
                        @elseif ($field->tipe === 'date')
                            <x-text-input id="field_{{ $field->id }}" class="mt-1 block w-full" type="date" wire:model="data.{{ $field->id }}" />
                        @else
                            <x-text-input id="field_{{ $field->id }}" class="mt-1 block w-full" wire:model="data.{{ $field->id }}" />
                        @endif
                        @error('data.' . $field->id) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="aktif" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        Active
                    </label>
                </div>

                @if ($this->masterItem->masterType->flag_ar || $this->masterItem->masterType->flag_ap)
                    <div class="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-medium text-gray-700">AR / AP Grouping</p>
                        <p class="mt-0.5 text-xs text-gray-500">Tentukan di sisi mana item ini boleh dipilih sebagai pihak transaksi. Kosongkan keduanya = netral.</p>
                        <div class="mt-3 flex flex-wrap gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="flagAr" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                AR (Piutang / Receivable)
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="flagAp" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                AP (Hutang / Payable)
                            </label>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Save</x-primary-button>
                <a href="{{ route('master-items.index', ['masterType' => $this->masterItem->master_type_id]) }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</div>