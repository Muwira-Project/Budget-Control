<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Chart of Accounts (COA)') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Manage the account master used in budgeting and actual transactions.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('imports.akuns') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <x-icon name="upload" class="h-4 w-4" /> Import
                </a>
                <a href="{{ route('exports.page', 'akuns') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-blue-600 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <x-icon name="download" class="h-4 w-4" /> Export
                </a>
                <a href="{{ route('akuns.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + Add Account
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this account? Related allocations and actuals will be affected.">
            <x-bulk-actions :paginator="$this->akuns" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="search" :value="__('Search Account')" />
                            <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search account code or name..." />
                        </div>
                        <div>
                            <x-input-label for="jenis_filter" :value="__('Filter Type')" />
                            <select id="jenis_filter" wire:model.live="jenisAkun" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Type</option>
                                <option value="pendapatan">Income</option>
                                <option value="pengeluaran">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="start_date" :value="__('From Date')" />
                            <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="end_date" :value="__('To Date')" />
                            <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>
                </div>

                @if ($this->akuns->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No accounts yet. Click "Add Account" to create the first account.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Account Name</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Category</th>
                                    <th class="px-6 py-3 text-right">Actual (Period)</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->akuns as $akun)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $akun->id }})" @checked(in_array($akun->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                            
                                        <td class="px-6 py-4 font-medium text-gray-900">{{ $akun->kode_akun }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $akun->nama_akun }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $akun->jenis_akun === \App\Enums\JenisAkun::Pendapatan ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $akun->jenis_akun->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $akun->kategori?->nama ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">
                                            {{ number_format($akun->actual_realtime ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
    <x-action-buttons :edit-href="route('akuns.edit', $akun)" :delete-id="$akun->id" />
</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->akuns" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
