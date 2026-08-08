<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Monitoring') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Period-based budget, actual, and variance per project or global.</p>
            </div>
            <a href="{{ route('monitoring.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Monitoring Period
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this monitoring period?">
            <x-bulk-actions :paginator="$this->periods" :selected-ids="$this->selectedIds" />
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="project_filter" :value="__('Filter Project')" />
                            <select id="project_filter" wire:model.live="projectFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Projects (Global)</option>
                                @foreach ($this->projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="search" :value="__('Search Number')" />
                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="e.g. MON-2026-001" />
                        </div>
                    </div>
                </div>

                @if ($this->periods->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' || $this->projectFilter !== null ? 'No monitoring periods match the filter.' : 'No monitoring periods yet. Click "Add Monitoring Period" to create the first period.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Number</th>
                                    
                                    <th class="px-6 py-3">Period</th>
                                    <th class="px-6 py-3">Week</th>
                                    <th class="px-6 py-3">Month</th>
                                    <th class="px-6 py-3 text-right">Budget</th>
                                    <th class="px-6 py-3 text-right">Actual</th>
                                    <th class="px-6 py-3 text-right">Variance</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->periods as $period)
                                    @php $totals = $this->totalsByPeriod[$period->id]; @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $period->id }})" @checked(in_array($period->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        <a href="{{ route('monitoring.show', $period) }}" wire:navigate class="text-blue-600 hover:text-blue-800 hover:underline">{{ $period->nomor }}</a>
                                    </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $period->project ? $period->project->kode.' - '.$period->project->nama : 'All Projects' }}</td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $period->periode_label }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $period->week }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $period->month }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($totals['budget']) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($totals['actual']) }}</td>
                                        <td class="px-6 py-4 text-right font-medium {{ $totals['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($totals['variance']) }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <a href="{{ route('monitoring.show', $period) }}" wire:navigate class="text-blue-600 hover:text-blue-800">Resume</a>
                                            <x-action-buttons :edit-href="route('monitoring.edit', $period)" :delete-id="$period->id" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->periods" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>


