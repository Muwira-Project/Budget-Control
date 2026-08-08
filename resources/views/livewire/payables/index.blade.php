<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Payables (AP)') }}</h2>
            <a href="{{ route('payables.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Payable
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this payable? All its payments will also be deleted.">
            <x-bulk-actions :paginator="$this->payables" :selected-ids="$this->selectedIds" />
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

                @if ($this->payables->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->projectId !== null || $this->statusFilter !== '' ? 'No payables match the filter.' : 'No payables yet. Payables are created automatically from actuals, or click "Add Payable".' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Item Account</th>
                                    <th class="px-6 py-3">Party</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Due Date</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Paid</th>
                                    <th class="px-6 py-3 text-right">Remaining</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->payables as $payable)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $payable->id }})" @checked(in_array($payable->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 text-gray-700">{{ $payable->project->kode }} - {{ $payable->project->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $payable->akun->kode_akun }} - {{ $payable->akun->nama_akun }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($payable->pihakJenis === 'vendor')
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">Vendor</span>
                                                {{ $payable->vendor?->nama }}
                                            @elseif ($payable->pihakJenis === 'supplier')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Supplier</span>
                                                {{ $payable->supplier?->nama }}
                                            @elseif ($payable->pihakJenis === 'mandor')
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Mandor</span>
                                                {{ $payable->mandor?->nama }}
                                            @elseif ($payable->pihakJenis === 'investor')
                                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">Investor</span>
                                                {{ $payable->investor?->nama }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $payable->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $payable->jatuh_tempo?->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900 font-medium">{{ format_idr($payable->nominal) }}</td>
                                        <td class="px-6 py-4 text-right text-green-600">{{ format_idr($payable->nominal_dibayar) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($payable->sisa) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($payable->status->value) {
                                                'lunas' => 'bg-green-100 text-green-700',
                                                'sebagian' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            } }}">
                                                {{ $payable->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('payables.edit', $payable)" :delete-id="$payable->id">
                                                <a href="{{ route('payables.pay', $payable) }}" wire:navigate title="Pay"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                    <x-icon name="banknotes" class="h-4 w-4" />
                                                </a>
                                            </x-action-buttons>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->payables" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
