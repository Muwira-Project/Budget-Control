<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50">
                <x-icon name="banknotes" class="h-5 w-5 text-brand-600" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">{{ __('Cash Flow per Account') }}</h2>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Cash Basis</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Pergerakan kas per rekening fisik. Hanya entri berstatus posted yang dihitung.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="start_date" :value="__('Start Date')" />
                <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
            </div>
            <div>
                <x-input-label for="end_date" :value="__('End Date')" />
                <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Saldo Awal</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ format_idr($this->report['totals']['saldo_awal']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Masuk</p>
                <p class="mt-1 text-lg font-bold text-emerald-600">{{ format_idr($this->report['totals']['masuk']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Keluar</p>
                <p class="mt-1 text-lg font-bold text-red-600">{{ format_idr($this->report['totals']['keluar']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Transfer (net)</p>
                <p class="mt-1 text-lg font-bold text-gray-700">{{ format_idr($this->report['totals']['tr_in'] - $this->report['totals']['tr_out']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Saldo Akhir</p>
                <p class="mt-1 text-lg font-bold text-brand-600">{{ format_idr($this->report['totals']['saldo_akhir']) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            @if (empty($this->report['rows']))
                <p class="p-6 text-sm text-gray-500">No cash accounts yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Account</th>
                                <th class="px-6 py-3 text-right">Saldo Awal</th>
                                <th class="px-6 py-3 text-right">Masuk</th>
                                <th class="px-6 py-3 text-right">Keluar</th>
                                <th class="px-6 py-3 text-right">Transfer In</th>
                                <th class="px-6 py-3 text-right">Transfer Out</th>
                                <th class="px-6 py-3 text-right">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->report['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700">{{ $row['kode'] }} - {{ $row['nama'] }} <span class="text-xs text-gray-400">({{ $row['jenis'] }})</span></td>
                                    <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['saldo_awal']) }}</td>
                                    <td class="px-6 py-4 text-right text-emerald-600">{{ format_idr($row['masuk']) }}</td>
                                    <td class="px-6 py-4 text-right text-red-600">{{ format_idr($row['keluar']) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['tr_in']) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['tr_out']) }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['saldo_akhir']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['saldo_awal']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-emerald-600">{{ format_idr($this->report['totals']['masuk']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-red-600">{{ format_idr($this->report['totals']['keluar']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['tr_in']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['tr_out']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['saldo_akhir']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>