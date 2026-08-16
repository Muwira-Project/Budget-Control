<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Cash Activity') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Semua pergerakan kas: manual, Payment Request, Non-Project Expense, AR/AP Settlement.</p>
            </div>
            <a href="{{ route('cashflows.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Income
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Cash In</p>
                <p class="mt-1 text-2xl font-bold text-green-600">{{ format_idr($this->stats['total_masuk']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Cash Out</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ format_idr($this->stats['total_keluar']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Cash Balance</p>
                <p class="mt-1 text-2xl font-bold text-blue-600">{{ format_idr($this->stats['saldo']) }}</p>
            </div>
            @if ($this->cashAccountId !== null && $this->stats['saldo_rekening'] !== null)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Account Balance</p>
                    <p class="mt-1 text-2xl font-bold text-brand-600">{{ format_idr($this->stats['saldo_rekening']) }}</p>
                </div>
            @else
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Lokasi Dana</p>
                    <p class="mt-1 text-sm text-gray-500">Filter rekening untuk melihat saldo buku besar.</p>
                </div>
            @endif
        </div>

        <x-confirm-modal message="Are you sure you want to delete this cash record?">
            <x-bulk-actions :paginator="$this->cashflows" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
                        </div>
                        <div>
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
                        </div>
                        <div>
                            <x-input-label for="cash_account_id" :value="__('Lokasi Dana')" />
                            <select id="cash_account_id" wire:model.live="cashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Accounts</option>
                                @foreach ($this->cashAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="jenis_filter" :value="__('Filter Type')" />
                            <select id="jenis_filter" wire:model.live="jenisFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All</option>
                                @foreach ($this->jenisOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <x-input-label for="sumber_filter" :value="__('Filter Source')" />
                            <select id="sumber_filter" wire:model.live="sumberFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Sources</option>
                                @foreach ($this->sumberOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($this->dateRangeInvalid)
                    <p class="p-6 text-sm text-red-600">Invalid date range: start date is later than end date.</p>
                @elseif ($this->cashflows->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->startDate !== null || $this->endDate !== null || $this->jenisFilter !== '' || $this->sumberFilter !== '' || $this->cashAccountId !== null ? 'No cash records match the filter.' : 'No cash records yet. Click "Add Income" or mark a payment request as paid.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Voucher</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Source</th>
                                    <th class="px-6 py-3">Account</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->cashflows as $entry)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $entry->id }})" @checked(in_array($entry->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>

                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $entry->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $entry->voucher?->nomor ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $entry->jenis->value === 'masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $entry->jenis->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $entry->sumber->label() }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $entry->cashAccount?->kode ?? '-' }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $entry->keterangan }}</td>
                                        <td class="px-6 py-4 text-right font-medium {{ $entry->jenis->value === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $entry->jenis->value === 'masuk' ? '+' : '-' }}{{ format_idr($entry->nominal) }}
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @if ($entry->isManual())
                                                <x-action-buttons :delete-id="$entry->id" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->cashflows" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>