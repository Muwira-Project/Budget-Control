<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Master Menu') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola sub-menu Master: tambah, edit, atau hapus menu master. Menu baru otomatis muncul di sidebar bawah menu Master dengan kolom sesuai definisi di bawah.</p>
            </div>
            <a href="{{ route('master-types.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Master Menu
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this master menu? All its data will be deleted.">
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                @if ($this->types->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No master menus yet. Click "Add Master Menu".</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">AR / AP</th>
                                    <th class="px-6 py-3 text-right">Fields</th>
                                    <th class="px-6 py-3 text-right">Items</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->types as $type)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $type->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $type->nama }}</td>
                                        <td class="px-6 py-4">
                                            @if ($type->flag_ar || $type->flag_ap)
                                                <span class="inline-flex items-center gap-1">
                                                    @if ($type->flag_ar)
                                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">AR</span>
                                                    @endif
                                                    @if ($type->flag_ap)
                                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">AP</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ $type->fields_count }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ $type->items_count }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $type->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $type->aktif ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('master-items.index', $type) }}" wire:navigate title="Data"
                                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                                                    <x-icon name="list" class="h-4 w-4" />
                                                </a>
                                                <x-action-buttons :edit-href="route('master-types.edit', $type)" :delete-id="$type->is_system ? null : $type->id" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->types" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>