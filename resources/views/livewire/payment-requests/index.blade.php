<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Payment Request') }}</h2>
            <a href="{{ route('payment-requests.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Payment Request
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

        <x-confirm-modal message="Are you sure you want to delete this payment request?">
            <x-bulk-actions :paginator="$this->paymentRequests" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                            <x-input-label for="prioritas_filter" :value="__('Filter Priority')" />
                            <select id="prioritas_filter" wire:model.live="prioritasFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Priorities</option>
                                @foreach ($this->prioritas as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="search" :value="__('Search Number/Project')" />
                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="e.g. PR-2026-001" />
                        </div>
                    </div>
                </div>

                @if ($this->paymentRequests->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->projectId !== null || $this->statusFilter !== '' || $this->prioritasFilter !== '' || $this->search !== '' ? 'No payment requests match the filter.' : 'No payment requests yet. Click "Add Payment Request" to create the first draft.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Number</th>
                                    <th class="px-6 py-3">Project</th>
                                    <th class="px-6 py-3">Item Account</th>
                                    <th class="px-6 py-3">Party</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Due Date</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Priority</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->paymentRequests as $pr)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $pr->id }})" @checked(in_array($pr->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $pr->nomor }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $pr->project->kode }} - {{ $pr->project->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $pr->akun->kode_akun }} - {{ $pr->akun->nama_akun }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($pr->pihakJenis === 'vendor')
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">Vendor</span>
                                                {{ $pr->vendor?->nama }}
                                            @elseif ($pr->pihakJenis === 'supplier')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Supplier</span>
                                                {{ $pr->supplier?->nama }}
                                            @elseif ($pr->pihakJenis === 'mandor')
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Mandor</span>
                                                {{ $pr->mandor?->nama }}
                                            @elseif ($pr->pihakJenis === 'investor')
                                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">Investor</span>
                                                {{ $pr->investor?->nama }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $pr->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $pr->jatuh_tempo?->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($pr->nominal) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($pr->prioritas->value) {
                                                'high' => 'bg-red-100 text-red-700',
                                                'medium' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-green-100 text-green-700',
                                            } }}">
                                                {{ $pr->prioritas->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($pr->status->value) {
                                                'waiting' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-blue-100 text-blue-700',
                                                'paid' => 'bg-green-100 text-green-700',
                                                'closed' => 'bg-gray-200 text-gray-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            } }}">
                                                {{ $pr->status->label() }}
                                            </span>
                                            @if ($pr->isHeld())
                                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">Held</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @if (in_array($pr->status->value, ['draft', 'rejected'], true))
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <x-action-buttons :edit-href="route('payment-requests.edit', $pr)" :delete-id="$pr->id">
                                                        <button type="button" wire:click="submit({{ $pr->id }})" title="Submit"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                            <x-icon name="upload" class="h-4 w-4" />
                                                        </button>
                                                        <button type="button" wire:click="cancel({{ $pr->id }})" title="Cancel"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-100">
                                                            <x-icon name="x-mark" class="h-4 w-4" />
                                                        </button>
                                                        @if (! $pr->isHeld())
                                                            <button type="button" wire:click="hold({{ $pr->id }})" title="Hold"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600">
                                                                <x-icon name="pause" class="h-4 w-4" />
                                                            </button>
                                                        @else
                                                            <button type="button" wire:click="release({{ $pr->id }})" title="Release"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                                <x-icon name="play" class="h-4 w-4" />
                                                            </button>
                                                        @endif
                                                    </x-action-buttons>
                                                </div>
                                            @elseif ($pr->status->value === 'waiting' && auth()->user()->isAdmin())
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="approve({{ $pr->id }})" title="Approve"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                        <x-icon name="check" class="h-4 w-4" />
                                                    </button>
                                                    <button type="button" wire:click="reject({{ $pr->id }})" title="Reject"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                        <x-icon name="x-mark" class="h-4 w-4" />
                                                    </button>
                                                    @if (! $pr->isHeld())
                                                        <button type="button" wire:click="hold({{ $pr->id }})" title="Hold"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600">
                                                            <x-icon name="pause" class="h-4 w-4" />
                                                        </button>
                                                    @else
                                                        <button type="button" wire:click="release({{ $pr->id }})" title="Release"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                            <x-icon name="play" class="h-4 w-4" />
                                                        </button>
                                                    @endif
                                                </div>
                                            @elseif ($pr->status->value === 'approved' && auth()->user()->isAdmin())
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="markPaid({{ $pr->id }})" title="Mark as Paid"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                        <x-icon name="banknotes" class="h-4 w-4" />
                                                    </button>
                                                    <button type="button" wire:click="close({{ $pr->id }})" title="Close"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-100">
                                                        <x-icon name="lock-closed" class="h-4 w-4" />
                                                    </button>
                                                    @if (! $pr->isHeld())
                                                        <button type="button" wire:click="hold({{ $pr->id }})" title="Hold"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600">
                                                            <x-icon name="pause" class="h-4 w-4" />
                                                        </button>
                                                    @else
                                                        <button type="button" wire:click="release({{ $pr->id }})" title="Release"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                            <x-icon name="play" class="h-4 w-4" />
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->paymentRequests" />
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
                        <h3 class="text-base font-semibold text-gray-900">Hold Payment Request</h3>
                        <p class="mt-1 text-sm text-gray-500">Alasan hold (prioritas bayar berdasarkan kondisi keuangan).</p>
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
