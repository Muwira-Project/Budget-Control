<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Investor Master') }}</h2>
            <a href="{{ route('investors.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Investor
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

        <x-confirm-modal message="Are you sure you want to delete this investor? Investors still used in actuals, payables, or payment requests cannot be deleted.">
            <x-bulk-actions :paginator="$this->investors" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="search" :value="__('Search Investor')" />
                        <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search investor code or name..." />
                    </div>
                </div>

                @if ($this->investors->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' ? 'No investors match your search.' : 'No investors yet. Click "Add Investor" to create the first investor.' }}
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
                                @foreach ($this->investors as $investor)
                                    <tr class="hover:bg-gray-50">
                                        <td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $investor->id }})" @checked(in_array($investor->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $investor->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $investor->nama }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $investor->telepon }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $investor->alamat }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('investors.edit', $investor)" :delete-id="$investor->id" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->investors" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
