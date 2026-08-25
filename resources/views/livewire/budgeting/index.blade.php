<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Budgeting') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Rencana budget (Budget Plan) dan alokasi per akun (Allocation) dalam satu tampilan.</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('budget-plans.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                        <x-icon name="clipboard" class="mr-1.5 h-4 w-4" /> Manage Budget Plan
                    </a>
                @endif
                @if (auth()->user()->isAdmin())
                    <button wire:click="createNonProjectAllocation" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        <x-icon name="plus" class="mr-1.5 h-4 w-4" /> Add Non-Project
                    </button>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        {{-- Summary cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Budget (Plan)</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ format_idr($this->summary['total_budget']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Allocation</p>
                <p class="mt-1 text-xl font-bold text-blue-600">{{ format_idr($this->summary['total_allocation']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Total Realisasi (Actual)</p>
                <p class="mt-1 text-xl font-bold text-emerald-600">{{ format_idr($this->summary['total_realisasi']) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            {{-- Filters --}}
            <div class="border-b border-gray-100 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                        <x-input-label for="status_filter" :value="__('Filter Status')" />
                        <select id="status_filter" wire:model.live="statusFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            @foreach ($this->statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search project or account..." />
                    </div>
                </div>
            </div>

            {{-- Table --}}
            @if ($this->rows->isEmpty())
                <p class="p-6 text-sm text-gray-500">
                    {{ $this->projectId !== null || $this->statusFilter !== '' || $this->search !== '' ? 'No data match the filter.' : 'No allocations yet. Click "Add Allocation" to create the first draft.' }}
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Budgeting Number</th>
                                <th class="px-6 py-3">Account</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Party / Name</th>
                                <th class="px-6 py-3 text-right">Outstanding</th>
                                <th class="px-6 py-3 text-right">Budget (Plan)</th>
                                <th class="px-6 py-3 text-right">Allocation</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($row['is_non_project'])
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">Non-Project</span>
                                        @else
                                            {{ $row['project']?->kode }} - {{ $row['project']?->nama }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $row['budgeting_number'] ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $row['akun']?->kode_akun }} - {{ $row['akun']?->nama_akun }}</td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($row['is_non_project'] && $row['allocation'])
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ match ($row['allocation']->type) {
                                                    'ap' => 'bg-red-100 text-red-700',
                                                    'ar' => 'bg-green-100 text-green-700',
                                                    'other_income' => 'bg-blue-100 text-blue-700',
                                                    'other_outcome' => 'bg-amber-100 text-amber-700',
                                                    default => 'bg-gray-100 text-gray-700',
                                                } }}">
                                                {{ $row['allocation']->type_label }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($row['is_non_project'] && $row['allocation'])
                                            {{ $row['allocation']->display_name }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-700">
                                        @if ($row['is_non_project'] && $row['allocation'] && $row['allocation']->outstanding_balance > 0)
                                            {{ format_idr($row['allocation']->outstanding_balance) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['budget']) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-700">
                                        {{ $row['allocation'] ? format_idr($row['allocation']->allocation) : '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($row['allocation'])
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($row['allocation']->status->value) {
                                                'waiting' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            } }}">
                                                {{ $row['allocation']->status->label() }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-400 ring-1 ring-gray-200">No Allocation</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($row['plan'] && ! $row['allocation'])
                                            @if (auth()->user()->isAdmin())
                                                <button type="button" wire:click="createAllocations({{ $row['plan']->budget_plan_id }})" title="Buat Allocation dari Plan"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                    <x-icon name="clipboard" class="h-4 w-4" />
                                                </button>
                                            @endif
                                        @elseif ($row['allocation'] && $row['allocation']->status->value === 'draft')
                                            <div class="flex items-center justify-end gap-1.5">
                                                <x-action-buttons :edit-href="route('allokasis.edit', $row['allocation'])" :delete-id="$row['allocation']->id">
                                                    <button type="button" wire:click="submit({{ $row['allocation']->id }})" title="Submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                        <x-icon name="upload" class="h-4 w-4" />
                                                    </button>
                                                </x-action-buttons>
                                            </div>
                                        @elseif ($row['allocation'] && $row['allocation']->status->value === 'waiting' && auth()->user()->isAdmin())
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="approve({{ $row['allocation']->id }})" title="Approve"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 transition hover:bg-green-100">
                                                    <x-icon name="check" class="h-4 w-4" /> Approve
                                                </button>
                                                <button type="button" wire:click="reject({{ $row['allocation']->id }})" title="Reject"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                                    <x-icon name="x-mark" class="h-4 w-4" /> Reject
                                                </button>
                                            </div>
                                        @elseif ($row['allocation'] && $row['allocation']->status->value === 'rejected')
                                            <x-action-buttons :edit-href="route('allokasis.edit', $row['allocation'])" :delete-id="$row['allocation']->id" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-6 py-4">
                    <x-pagination-footer :paginator="$this->rows" />
                </div>
            @endif
        </div>
    </div>
</div>
