<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Receivables (AR)') }}</h2>
            <a href="{{ route('receivables.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Receivable
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this receivable? All its payments will also be deleted.">
            <x-bulk-actions :paginator="$this->receivables" :selected-ids="$this->selectedIds" />
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

                @if ($this->receivables->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->projectId !== null || $this->statusFilter !== '' ? 'No receivables match the filter.' : 'No receivables yet. Receivables are created automatically when a project is completed, or click "Add Receivable".' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Paid</th>
                                    <th class="px-6 py-3 text-right">Remaining</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->receivables as $receivable)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $receivable->id }})" @checked(in_array($receivable->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 text-gray-700">{{ $receivable->project->kode }} - {{ $receivable->project->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $receivable->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $receivable->jatuh_tempo?->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900 font-medium">{{ format_idr($receivable->nominal) }}</td>
                                        <td class="px-6 py-4 text-right text-green-600">{{ format_idr($receivable->nominal_dibayar) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($receivable->sisa) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($receivable->status->value) {
                                                'lunas' => 'bg-green-100 text-green-700',
                                                'sebagian' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            } }}">
                                                {{ $receivable->status->label() }}
                                            </span>
                                            @if ($receivable->isHeld())
                                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">Held</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('receivables.edit', $receivable)" :delete-id="$receivable->id">
                                                <a href="{{ route('receivables.pay', $receivable) }}" wire:navigate title="Pay"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                    <x-icon name="banknotes" class="h-4 w-4" />
                                                </a>
                                                @if (! $receivable->isHeld() && $receivable->sisa > 0)
                                                    <button type="button" wire:click="hold({{ $receivable->id }})" title="Hold"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600">
                                                        <x-icon name="pause" class="h-4 w-4" />
                                                    </button>
                                                @elseif ($receivable->isHeld())
                                                    <button type="button" wire:click="release({{ $receivable->id }})" title="Release"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                        <x-icon name="play" class="h-4 w-4" />
                                                    </button>
                                                @endif
                                            </x-action-buttons>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->receivables" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>

    @if ($this->holdingId !== null)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="$set('holdingId', null)"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">Hold Receivable</h3>
                        <p class="mt-1 text-sm text-gray-500">Tahan penerimaan yang belum tercatat; pelunasan diblokir sampai dilepas.</p>
                        <textarea wire:model="holdReason" rows="3" placeholder="Alasan hold..." class="mt-4 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                        <button type="button" wire:click="$set('holdingId', null)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="confirmHold" class="inline-flex items-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500">Hold</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
