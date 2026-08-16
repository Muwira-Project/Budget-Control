<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50">
                    <x-icon name="chart-pie" class="h-5 w-5 text-brand-600" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold text-gray-900 leading-tight">{{ __('Profit & Loss') }}</h2>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Accrual Basis</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Nilai kontrak (revenue) vs biaya realisasi (cost) per project. Profit = Revenue - Cost.</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="start_date" :value="__('Start Date')" />
                <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
            </div>
            <div>
                <x-input-label for="end_date" :value="__('End Date')" />
                <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
            </div>
            <div>
                <x-input-label for="project_id" :value="__('Filter Project')" />
                <select id="project_id" wire:model.live="projectId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Projects</option>
                    @foreach ($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Revenue (Contract)</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ format_idr($this->report['totals']['revenue']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Cost (Realisasi)</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ format_idr($this->report['totals']['cost']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Profit / Loss</p>
                <p class="mt-1 text-2xl font-bold {{ $this->report['totals']['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_idr($this->report['totals']['profit']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Margin</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $this->report['totals']['margin'] }}%</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            @if (empty($this->report['rows']))
                <p class="p-6 text-sm text-gray-500">No projects to report. Buat project dulu atau ubah filter.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3 text-right">Revenue</th>
                                <th class="px-6 py-3 text-right">Cost</th>
                                <th class="px-6 py-3 text-right">Profit</th>
                                <th class="px-6 py-3 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->report['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700">{{ $row['kode'] }} - {{ $row['nama'] }}</td>
                                    <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['revenue']) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['cost']) }}</td>
                                    <td class="px-6 py-4 text-right font-medium {{ $row['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_idr($row['profit']) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700">{{ $row['margin'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['revenue']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['cost']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold {{ $this->report['totals']['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_idr($this->report['totals']['profit']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ $this->report['totals']['margin'] }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>