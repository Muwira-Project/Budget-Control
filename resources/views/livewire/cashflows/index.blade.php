<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Cash Activity') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Cash In, Cash Out, dan Fund Transfer dalam satu tempat. Voucher diakses lewat tombol aksi per transaksi.</p>
            </div>
            @if (in_array($this->tab, ['cash-in', 'cash-out'], true))
                <a href="{{ route('cashflows.create', ['mode' => $this->tab === 'cash-in' ? 'masuk' : 'keluar']) }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + {{ $this->tab === 'cash-in' ? 'Add Cash In' : 'Add Cash Out' }}
                </a>
            @elseif ($this->tab === 'fund-transfer')
                <a href="{{ route('fund-transfers.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + Add Fund Transfer
                </a>
            @endif
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 flex flex-wrap gap-2">
            @foreach ([
                'cash-in' => 'Cash In',
                'cash-out' => 'Cash Out',
                'fund-transfer' => 'Fund Transfer',
                'cash-account' => 'Cash Account',
            ] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $this->tab === $key ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if (in_array($this->tab, ['cash-in', 'cash-out'], true))
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
                            <x-input-label for="sumber_filter" :value="__('Source')" />
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
                        {{ $this->startDate !== null || $this->endDate !== null || $this->sumberFilter !== '' || $this->cashAccountId !== null ? 'No cash records match the filter.' : 'No ' . strtolower($this->tab === 'cash-in' ? 'cash in' : 'cash out') . ' records yet. Click the add button to create one.' }}
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
                                    <th class="px-6 py-3">Status</th>
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
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                            @if ($entry->voucher)
                                                <button type="button" wire:click="viewVoucher({{ $entry->id }})" title="View Voucher" class="inline-flex items-center gap-1 rounded border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                                                    <x-icon name="document" class="h-3.5 w-3.5" />
                                                    {{ $entry->voucher->nomor }}
                                                </button>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $entry->jenis->value === 'masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $entry->jenis->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $entry->sumber->label() }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $entry->cashAccount?->kode ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($entry->status->value) {
                                                'posted' => 'bg-green-100 text-green-700',
                                                'waiting' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-blue-100 text-blue-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-200 text-gray-600',
                                            } }}">
                                                {{ $entry->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $entry->keterangan }}</td>
                                        <td class="px-6 py-4 text-right font-medium {{ $entry->jenis->value === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $entry->jenis->value === 'masuk' ? '+' : '-' }}{{ format_idr($entry->nominal) }}
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @if ($entry->isManual())
                                                <x-action-buttons :delete-id="$entry->id">
                                                    @if ($entry->status->value === 'draft')
                                                        <button type="button" wire:click="submit({{ $entry->id }})" title="Submit for Approval"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                            <x-icon name="upload" class="h-4 w-4" />
                                                        </button>
                                                    @endif
                                                </x-action-buttons>
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
        @elseif ($this->tab === 'fund-transfer')
            <div class="mt-6">
                <livewire:fund-transfers.index />
            </div>
        @elseif ($this->tab === 'cash-account')
            <div class="mt-6">
                <livewire:cash-accounts.index />
            </div>
        @endif
    </div>

    @if ($this->voucherId !== null)
        @php $voucherEntry = \App\Models\Cashflow::with('voucher')->find($this->voucherId); @endphp
        @if ($voucherEntry && $voucherEntry->voucher)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="closeVoucher"></div>
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-900">Voucher</h3>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Number</dt><dd class="font-medium text-gray-900">{{ $voucherEntry->voucher->nomor }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Date</dt><dd class="font-medium text-gray-900">{{ $voucherEntry->voucher->tanggal->format('d M Y') }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Type</dt><dd class="font-medium text-gray-900">{{ ucfirst($voucherEntry->voucher->jenis) }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Amount</dt><dd class="font-medium text-gray-900">{{ format_idr($voucherEntry->nominal) }}</dd></div>
                                @if ($voucherEntry->voucher->keterangan)
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Description</dt><dd class="text-gray-900">{{ $voucherEntry->voucher->keterangan }}</dd></div>
                                @endif
                            </dl>
                        </div>
                        <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="closeVoucher" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>