<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ $this->masterType->nama }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Kode {{ $this->masterType->kode }}
                    @if ($this->masterType->flag_ar) <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">AR</span>@endif
                    @if ($this->masterType->flag_ap) <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">AP</span>@endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('master-types.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    &larr; Back
                </a>
                <a href="{{ route('master-items.create', $this->masterType) }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + Add {{ $this->masterType->nama }}
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this item?">
            <x-bulk-actions :paginator="$this->items" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search item code or name..." />
                    </div>
                </div>

                @if ($this->items->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No items yet. Click the add button.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    @foreach ($this->masterType->fields as $field)
                                        <th class="px-6 py-3">{{ $field->label }}</th>
                                    @endforeach
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->items as $item)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $item->id }})" @checked(in_array($item->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>

                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $item->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $item->nama }}</td>
                                        @foreach ($this->masterType->fields as $field)
                                            <td class="px-6 py-4 text-gray-500">{{ $item->data[(string) $field->id] ?? '-' }}</td>
                                        @endforeach
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $item->aktif ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('master-items.edit', $item)" :delete-id="$item->id" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->items" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>