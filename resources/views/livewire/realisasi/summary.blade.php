<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Actual') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan realisasi per proyek dan akun (agregat).</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="project_id" :value="__('Project')" />
                    <select id="project_id" wire:model.live="projectId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Projects</option>
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
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

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Actual</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">{{ format_idr($this->total) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Project-Account Rows</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $this->rows->count() }}</p>
            </div>
        </div>

        @if ($this->dateRangeInvalid)
            <p class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-600">Invalid date range: start date is later than end date.</p>
        @elseif ($this->rows->isEmpty())
            <p class="mt-6 rounded-2xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">No actual records match the filter.</p>
        @else
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Account</th>
                                <th class="px-6 py-3 text-right">Transactions</th>
                                <th class="px-6 py-3 text-right">Total Actual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($row['project'])
                                            <span class="font-medium text-gray-900">{{ $row['project']->kode }}</span>
                                            <span class="text-gray-500"> - {{ $row['project']->nama }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($row['akun'])
                                            {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500">{{ $row['jumlah_transaksi'] }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['nominal']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Total</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_idr($this->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
