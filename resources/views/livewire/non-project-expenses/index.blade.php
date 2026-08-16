<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="coins" title="Non-Project Expense" description="Expenses outside projects, tied to an account (COA).">
            <x-slot:actions>
                <a href="{{ route('non-project-expenses.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="plus" class="h-4 w-4" /> Add Expense
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this non-project expense?">
            <x-bulk-actions :paginator="$this->expenses" :selected-ids="$this->selectedIds" />
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="search" :value="__('Search Description')" />
                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search description..." />
                        </div>
                        <div>
                            <x-input-label for="akun_filter" :value="__('Filter Account')" />
                            <select id="akun_filter" wire:model.live="akunId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Accounts</option>
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
                </div>

                @if ($this->expenses->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No non-project expenses yet. Click "Add Expense" to record the first one.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Account</th>
                                    <th class="px-6 py-3">Party</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->expenses as $expense)
                                    <tr class="hover:bg-gray-50">
                                        <td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $expense->id }})" @checked(in_array($expense->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $expense->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($expense->status->value) {
                                                'posted' => 'bg-green-100 text-green-700',
                                                'waiting' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-blue-100 text-blue-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-200 text-gray-600',
                                            } }}">
                                                {{ $expense->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $expense->akun->kode_akun }} - {{ $expense->akun->nama_akun }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($expense->pihakJenis)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($expense->pihakJenis) {
                                                    'vendor' => 'bg-blue-100 text-blue-700',
                                                    'supplier' => 'bg-emerald-100 text-emerald-700',
                                                    'mandor' => 'bg-indigo-100 text-indigo-700',
                                                    default => 'bg-purple-100 text-purple-700',
                                                } }}">
                                                    {{ ucfirst($expense->pihakJenis) }}
                                                </span>
                                                {{ $expense->pihak }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $expense->keterangan }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($expense->nominal) }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons
                                                :edit-href="in_array($expense->status->value, ['draft', 'waiting'], true) ? route('non-project-expenses.edit', $expense) : null"
                                                :delete-id="$expense->isPosted() ? null : $expense->id">
                                                @if ($expense->status->value === 'draft')
                                                    <button type="button" wire:click="submit({{ $expense->id }})" title="Submit for Approval"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                        <x-icon name="upload" class="h-4 w-4" />
                                                    </button>
                                                @endif
                                            </x-action-buttons>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="5" class="px-6 py-3 font-semibold text-gray-900">Total (halaman ini)</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->totalNominal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->expenses" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>