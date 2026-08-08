<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="clipboard" title="Budget" description="Plan estimated income, costs, and target profit per period.">
            <x-slot:actions>
                <a href="{{ route('budget-plans.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="plus" class="h-4 w-4" /> Add Budget
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this Budget? All details per account will also be deleted.">
            <x-bulk-actions :paginator="$this->budgetPlans" :selected-ids="$this->selectedIds" />
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
                            <x-input-label for="search" :value="__('Search Project')" />
                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search project code or name..." />
                        </div>
                    </div>
                </div>

                @if ($this->budgetPlans->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' || $this->projectId !== null ? 'No Budgets match the filter.' : 'No Budgets yet. Click "Add Budget" to create the first plan.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Period</th>
                                    <th class="px-6 py-3 text-right">Estimated Income</th>
                                    <th class="px-6 py-3 text-right">Estimated Biaya</th>
                                    <th class="px-6 py-3 text-right">Target Profit</th>
                                    <th class="px-6 py-3 text-center">Account</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->budgetPlans as $plan)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $plan->id }})" @checked(in_array($plan->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 text-gray-700">{{ $plan->project->kode }} - {{ $plan->project->nama }}</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $plan->periode }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($plan->estimasi_pendapatan) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($plan->total_budget) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($plan->target_laba) }}</td>
                                        <td class="px-6 py-4 text-center text-gray-700">{{ $plan->items->count() }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('budget-plans.edit', $plan)" :delete-id="$plan->id">
                                                <button type="button" wire:click="createAllocations({{ $plan->id }})" title="Buat Allocation"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                    <x-icon name="clipboard" class="h-4 w-4" />
                                                </button>
                                            </x-action-buttons>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->budgetPlans" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>

