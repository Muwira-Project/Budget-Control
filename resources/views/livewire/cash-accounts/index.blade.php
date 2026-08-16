<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Cash Account') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Buku besar lokasi dana: Kas Kecil, Bank BCA, Bank Mandiri, dst.</p>
            </div>
            <a href="{{ route('cash-accounts.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Cash Account
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this cash account?">
            <x-bulk-actions :paginator="$this->accounts" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="status_filter" :value="__('Filter Status')" />
                        <select id="status_filter" wire:model.live="statusFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                @if ($this->accounts->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No cash accounts yet. Click "Add Cash Account".</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3 text-right">Opening Balance</th>
                                    <th class="px-6 py-3 text-right">Current Balance</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->accounts as $account)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $account->id }})" @checked(in_array($account->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>

                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $account->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            {{ $account->nama }}
                                            @if ($account->is_default)
                                                <span class="ml-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Default</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $account->jenis->label() }}</td>
                                        <td class="px-6 py-4 text-right text-gray-500">{{ format_idr($account->saldo_awal) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($account->balance ?? $account->saldo) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $account->status->value === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $account->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('cash-accounts.edit', $account)" :delete-id="$account->id" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->accounts" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>