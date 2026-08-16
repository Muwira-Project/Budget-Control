<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">{{ __('Aging AR/AP') }}</h2>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Per Jatuh Tempo</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Sisa piutang (AR) dan hutang (AP) berdasarkan umur jatuh tempo.</p>
            </div>
            <div class="w-full sm:w-56">
                <x-input-label for="as_of" :value="__('As Of Date')" />
                <x-text-input id="as_of" class="mt-1 block w-full" type="date" wire:model.live="asOf" />
            </div>
        </div>

        @php
            $bucketLabels = ['current' => 'Current', '1_30' => '1-30 days', '31_60' => '31-60 days', '61_90' => '61-90 days', 'over_90' => '> 90 days'];
        @endphp

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-semibold text-gray-700">AR Outstanding per Bucket</p>
                <div class="mt-4 space-y-3">
                    @foreach ($bucketLabels as $key => $label)
                        <div class="flex items-center justify-between border-b border-gray-50 pb-2 text-sm">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-semibold text-gray-900">{{ format_idr($this->report['ar_totals'][$key]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-semibold text-gray-700">AP Outstanding per Bucket</p>
                <div class="mt-4 space-y-3">
                    @foreach ($bucketLabels as $key => $label)
                        <div class="flex items-center justify-between border-b border-gray-50 pb-2 text-sm">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-semibold text-gray-900">{{ format_idr($this->report['ap_totals'][$key]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Account Receivable (AR) - Sisa Tagihan</h3>
                </div>
                @if (empty($this->report['ar_rows']))
                    <p class="p-6 text-sm text-gray-500">Tidak ada piutang outstanding.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Due</th>
                                    <th class="px-6 py-3 text-right">Remaining</th>
                                    <th class="px-6 py-3">Bucket</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->report['ar_rows'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-700">{{ $row['label'] }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $row['tanggal'] }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $row['jatuh_tempo'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['sisa']) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $row['bucket'] === 'over_90' || $row['bucket'] === '61_90' ? 'bg-red-100 text-red-700' : ($row['bucket'] === 'current' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $bucketLabels[$row['bucket']] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Account Payable (AP) - Sisa Hutang</h3>
                </div>
                @if (empty($this->report['ap_rows']))
                    <p class="p-6 text-sm text-gray-500">Tidak ada hutang outstanding.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Party</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Due</th>
                                    <th class="px-6 py-3 text-right">Remaining</th>
                                    <th class="px-6 py-3">Bucket</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->report['ap_rows'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-700">{{ $row['label'] }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $row['tanggal'] }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $row['jatuh_tempo'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['sisa']) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $row['bucket'] === 'over_90' || $row['bucket'] === '61_90' ? 'bg-red-100 text-red-700' : ($row['bucket'] === 'current' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $bucketLabels[$row['bucket']] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>