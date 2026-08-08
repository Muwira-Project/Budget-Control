<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Period Resume') }} â€” {{ $monitoringPeriod->nomor }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $monitoringPeriod->periode_label }}
                    @if ($monitoringPeriod->project)
                        Â· {{ $monitoringPeriod->project->kode }} - {{ $monitoringPeriod->project->nama }}
                    @else
                        Â· All Projects (Global)
                    @endif
                    Â· Week {{ $monitoringPeriod->week }} Â· {{ $monitoringPeriod->month }}
                </p>
            </div>
            <a href="{{ route('monitoring.index') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">Back to Monitoring</a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Budget</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_idr($this->totals['budget']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Actual</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_idr($this->totals['actual']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Variance</p>
                <p class="mt-2 text-2xl font-bold {{ $this->totals['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($this->totals['variance']) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Account</th>
                            <th class="px-6 py-3 text-right">Budget</th>
                            <th class="px-6 py-3 text-right">Actual</th>
                            <th class="px-6 py-3 text-right">Variance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($this->breakdown as $row)
                            <tr class="hover:bg-gray-50 cursor-pointer transition" wire:click="$dispatch('showRealizationDetail', { akunId: {{ $row['akun']->id }}, periodId: {{ $monitoringPeriod->id }} })">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900">{{ $row['akun']->kode_akun }}</span>
                                    <span class="text-gray-500"> - {{ $row['akun']->nama_akun }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['budget']) }}</td>
                                <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['actual']) }}</td>
                                <td class="px-6 py-4 text-right font-medium {{ $row['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($row['variance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-sm text-gray-500">No budget or actual data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ format_idr($this->totals['budget']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ format_idr($this->totals['actual']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold {{ $this->totals['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($this->totals['variance']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

