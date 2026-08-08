<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Actual') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Record and review actual project spending by account.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('imports.realisasi') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <x-icon name="upload" class="h-4 w-4" /> Import
                </a>
                <a href="{{ route('exports.page', 'realisasi') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <x-icon name="download" class="h-4 w-4" /> Export
                </a>
                <a href="{{ route('realisasi.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + Add Actual
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this actual? Total actual and remaining budget will be recalculated automatically.">
            <x-bulk-actions :paginator="$this->realisasi" :selected-ids="$this->selectedIds" />
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
                    @if ($this->startDate || $this->endDate)
                        <p class="mt-3 text-sm text-gray-500">The actual list is filtered according to the selected date range.</p>
                    @endif
                </div>

                @if ($this->dateRangeInvalid)
                    <p class="p-6 text-sm text-red-600">Invalid date range: start date is later than end date.</p>
                @elseif ($this->realisasi->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No actual records yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Item Account</th>
                                    <th class="px-6 py-3">Vendor / Supplier</th>
                                    <th class="px-6 py-3">Category</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->realisasi as $item)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $item->id }})" @checked(in_array($item->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $item->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $item->project->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $item->akun->kode_akun }} - {{ $item->akun->nama_akun }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($item->pihakJenis === 'vendor')
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">Vendor</span>
                                                {{ $item->vendor?->nama }}
                                            @elseif ($item->pihakJenis === 'supplier')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Supplier</span>
                                                {{ $item->supplier?->nama }}
                                            @elseif ($item->pihakJenis === 'mandor')
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Mandor</span>
                                                {{ $item->mandor?->nama }}
                                            @elseif ($item->pihakJenis === 'investor')
                                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">Investor</span>
                                                {{ $item->investor?->nama }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($item->kategori)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ $item->kategori->nama }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $item->keterangan }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">
                                            <a href="{{ route('realisasi.edit', $item) }}" wire:navigate class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer">{{ format_idr($item->nominal) }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
    <x-action-buttons :edit-href="route('realisasi.edit', $item)" :delete-id="$item->id" />
</td>
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
        </x-confirm-modal>
    </div>
</div>



