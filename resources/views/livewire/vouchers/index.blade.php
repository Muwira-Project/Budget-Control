<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Voucher') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Nomor seri otomatis + tanggal untuk setiap catatan kas.</p>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
                    </div>
                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
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
                </div>
            </div>

            @if ($this->vouchers->isEmpty())
                <p class="p-6 text-sm text-gray-500">No vouchers yet. Vouchers are generated automatically for cash entries.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Voucher No.</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->vouchers as $voucher)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $voucher->nomor }}</td>
                                    <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $voucher->tanggal->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $voucher->jenis === 'masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $voucher->jenis === 'masuk' ? 'Income' : 'Expense' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">{{ $voucher->cashflow?->keterangan ?? $voucher->keterangan }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900">{{ $voucher->cashflow ? format_idr($voucher->cashflow->nominal) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-6 py-4">
                    <x-pagination-footer :paginator="$this->vouchers" />
                </div>
            @endif
        </div>
    </div>
</div>