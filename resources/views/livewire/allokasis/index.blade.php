<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Budget Allocation') }}</h2>
            <a href="{{ route('allokasis.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Allocation
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this allocation?">
            <x-bulk-actions :paginator="$this->allocations" :selected-ids="$this->selectedIds" />
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
                            <x-input-label for="status_filter" :value="__('Filter Status')" />
                            <select id="status_filter" wire:model.live="statusFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                @foreach ($this->statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($this->allocations->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->projectId !== null || $this->statusFilter !== '' ? 'No allocations match the filter.' : 'No allocations yet. Click "Add Allocation" to create the first draft.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Item Account</th>
                                    <th class="px-6 py-3 text-right">Budget</th>
                                    <th class="px-6 py-3 text-right">Allocation</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->allocations as $allocation)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $allocation->id }})" @checked(in_array($allocation->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 text-gray-700">{{ $allocation->project->kode }} - {{ $allocation->project->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $allocation->akun->kode_akun }} - {{ $allocation->akun->nama_akun }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($allocation->budget) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($allocation->allocation) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($allocation->status->value) {
                                                'waiting' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            } }}">
                                                {{ $allocation->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @if ($allocation->status->value === 'draft')
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <x-action-buttons :edit-href="route('allokasis.edit', $allocation)" :delete-id="$allocation->id">
                                                        <button type="button" wire:click="submit({{ $allocation->id }})" title="Submit"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                            <x-icon name="upload" class="h-4 w-4" />
                                                        </button>
                                                    </x-action-buttons>
                                                </div>
                                            @elseif ($allocation->status->value === 'waiting' && auth()->user()->isAdmin())
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="approve({{ $allocation->id }})" title="Approve"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                        <x-icon name="check" class="h-4 w-4" />
                                                    </button>
                                                    <button type="button" wire:click="reject({{ $allocation->id }})" title="Reject"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                        <x-icon name="x-mark" class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            @elseif ($allocation->status->value === 'rejected')
                                                <x-action-buttons :edit-href="route('allokasis.edit', $allocation)" :delete-id="$allocation->id" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->allocations" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
