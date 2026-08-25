<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="folder" title="Project" description="Manage projects, contract values, and their budget scope.">
            <x-slot:actions>
                <a href="{{ route('imports.projects.template') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="download" class="h-4 w-4" /> Download Template
                </a>
                <a href="{{ route('imports.projects') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="upload" class="h-4 w-4" /> Import Projects
                </a>
                <a href="{{ route('imports.projects.export', ['periode' => $this->filterPeriode]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="download" class="h-4 w-4" /> Export
                </a>
                <a href="{{ route('projects.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="plus" class="h-4 w-4" /> Add Project
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this project? All related budgets and actuals will also be deleted.">
            <x-bulk-actions :paginator="$this->projects" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="max-w-sm">
                                            <x-input-label for="search" :value="__('Search Project')" />
                                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search project code or name..." />
                                        </div>

                                        <div class="max-w-sm">
                                            <x-input-label for="filter_periode" :value="__('Filter Periode')" />
                                            <select id="filter_periode" wire:model="filterPeriode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">All Periods</option>
                                                @foreach ($this->availablePeriodes as $p)
                                                    <option value="{{ $p }}">{{ $p }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="max-w-sm">
                                            <x-input-label for="filter_status" :value="__('Filter Status')" />
                                            <select id="filter_status" wire:model="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                @foreach ($this->availableStatuses as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="max-w-sm">
                                            <x-input-label for="filter_pic" :value="__('Filter PIC')" />
                                            <select id="filter_pic" wire:model="filterPic" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">All PICs</option>
                                                @foreach ($this->availablePics as $pic)
                                                    <option value="{{ $pic }}">{{ $pic }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                @if ($this->projects->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' ? 'No projects match your search.' : 'No projects yet. Click "Add Project" to create the first project.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Location</th>
                                    <th class="px-6 py-3">Division</th>
                                    <th class="px-6 py-3">PIC</th>
                                    <th class="px-6 py-3 text-right">Value (incl. tax)</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->projects as $project)
                                    <tr class="cursor-pointer transition hover:bg-gray-50" wire:click="showProjectDetail({{ $project->id }})">
<td class="w-8 px-6 py-4" wire:click.stop><input type="checkbox" wire:click="toggleSelected({{ $project->id }})" @checked(in_array($project->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 font-medium text-blue-600 hover:text-blue-800">{{ $project->kode }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $project->nama }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $project->jenis === \App\Enums\ProjectJenis::Jasa ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $project->jenis->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $project->lokasi }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $project->division?->nama ?? '-' }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $project->pic ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900">
                                            @if ($project->nilai_total > 0)
                                                {{ format_idr($project->nilai_total) }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($project->status->value) {
                                                'draft' => 'bg-slate-100 text-slate-700',
                                                'progress' => 'bg-blue-100 text-blue-700',
                                                'revisi' => 'bg-amber-100 text-amber-700',
                                                'done' => 'bg-green-100 text-green-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            } }}">
                                                {{ $project->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap" wire:click.stop>
    <x-action-buttons :edit-href="route('projects.edit', $project)" :delete-id="$project->id" />
</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->projects" />
                    </div>
                @endif
            </div>

    {{-- Project Detail Modal --}}
    @if ($this->selectedProjectId && $this->selectedProject)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" wire:click="closeDetails"></div>
            <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Project Detail</p>
                        <h3 class="mt-0.5 font-semibold text-gray-900">{{ $this->selectedProject->kode }} — {{ $this->selectedProject->nama }}</h3>
                    </div>
                    <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-2 border-b border-gray-100 bg-slate-50/60 px-6 py-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Contract</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->selectedProject->nilai_total) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Budget</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->selectedProject->budget_total ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Actual</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->projectRealisations->sum('nominal')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Transactions</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $this->projectRealisations->count() }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b border-gray-100 px-6 py-3 sm:grid-cols-5">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">PIC</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $this->selectedProject->pic ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Division</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $this->selectedProject->division?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Category</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $this->selectedProject->projectCategory?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Sub Work</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $this->selectedProject->sub_work ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Period</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $this->selectedProject->periode ?? '-' }}</p>
                    </div>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Account</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Party</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($this->projectRealisations as $realisasi)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</td>
                                    <td class="px-6 py-3">
                                        @if ($realisasi->kategori)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $realisasi->kategori->nama }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        @if ($realisasi->pihakJenis && $realisasi->pihak)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ ucfirst($realisasi->pihakJenis) }}</span> {{ $realisasi->pihak }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-500">{{ $realisasi->keterangan }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-gray-900">{{ format_idr($realisasi->nominal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">Belum ada transaksi realisasi untuk project ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->projectRealisations->sum('nominal')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
        </x-confirm-modal>
    </div>
</div>