<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Mandor Master') }}</h2>
            <a href="{{ route('mandors.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Mandor
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this mandor? Mandors still used in actuals, payables, or payment requests cannot be deleted.">
            <x-bulk-actions :paginator="$this->mandors" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="search" :value="__('Search Mandor')" />
                        <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search mandor code or name..." />
                    </div>
                </div>

                @if ($this->mandors->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' ? 'No mandors match your search.' : 'No mandors yet. Click "Add Mandor" to create the first mandor.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Phone</th>
                                    <th class="px-6 py-3">Address</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->mandors as $mandor)
                                    <tr class="hover:bg-gray-50">
                                        <td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $mandor->id }})" @checked(in_array($mandor->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $mandor->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $mandor->nama }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $mandor->telepon }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $mandor->alamat }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('mandors.edit', $mandor)" :delete-id="$mandor->id" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->mandors" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
