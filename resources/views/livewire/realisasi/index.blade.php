<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="trending-up" title="Actual" description="Ledger of actual transactions generated automatically from payments.">
            <x-slot:actions>
                <a href="{{ route('exports.page', 'realisasi') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="download" class="h-4 w-4" /> Export
                </a>
            </x-slot:actions>
        </x-page-header>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="project_filter" :value="__('Filter Project')" />
                        <select id="project_filter" wire:model.live="projectId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Projects</option>
                            @foreach ($this->projects as $project)
                                <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="akun_filter" :value="__('Filter Account Item')" />
                        <select id="akun_filter" wire:model.live="akunId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Item Accounts</option>
                            @foreach ($this->akuns as $akun)
                                <option value="{{ $akun->id }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
                    </div>
                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
                    </div>
                </div>
            </div>

            @if ($this->dateRangeInvalid)
                <p class="p-6 text-sm text-red-600">Invalid date range: start date is later than end date.</p>
            @elseif ($this->realisasi->isEmpty())
                <p class="p-6 text-sm text-gray-500">No actual records yet. Actual transactions are generated automatically when payment requests are paid or payables are settled.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Source</th>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Item Account</th>
                                <th class="px-6 py-3">Vendor / Supplier</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->realisasi as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $item->tanggal->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->sumber === \App\Models\Realisasi::SUMBER_AP_PAYMENT ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $item->sumber_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $item->project->nama ?? 'Non-Project' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $item->akun->kode_akun }} - {{ $item->akun->nama_akun }}</td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($item->pihakJenis && $item->pihak)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ ucfirst($item->pihakJenis) }}</span>
                                            {{ $item->pihak }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">{{ $item->keterangan }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($item->nominal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-6 py-4">
                    <x-pagination-footer :paginator="$this->realisasi" />
                </div>
            @endif
        </div>
    </div>
</div>