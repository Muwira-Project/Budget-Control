<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="banknotes" title="Account" description="Manage the account master used in budgeting and actual transactions.">
            <x-slot:actions>
                <a href="{{ route('imports.akuns') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="upload" class="h-4 w-4" /> Import
                </a>
                <a href="{{ route('exports.page', 'akuns') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                    <x-icon name="download" class="h-4 w-4" /> Export
                </a>
                <a href="{{ route('akuns.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="plus" class="h-4 w-4" /> Add Account
                </a>
            </x-slot:actions>
        </x-page-header>

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
                                        <td class="px-6 py-4 text-right">
                                            <button type="button" wire:click="showAccountDetail({{ $akun->id }})" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 font-medium text-blue-600 transition hover:bg-blue-50 hover:text-blue-800">
                                                {{ number_format($akun->actual_realtime ?? 0, 2) }}
                                                <x-icon name="eye" class="h-4 w-4" />
                                            </button>
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

    {{-- Account Detail Modal --}}
    @if ($this->selectedAkunId && $this->selectedAkun)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" wire:click="closeDetails"></div>
            <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Account Detail</p>
                        <h3 class="mt-0.5 font-semibold text-gray-900">{{ $this->selectedAkun->kode_akun }} — {{ $this->selectedAkun->nama_akun }}</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Periode {{ $this->startDate ?? '...' }} → {{ $this->endDate ?? '...' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Party</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($this->akunRealisations as $realisasi)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $realisasi->project->kode }} - {{ $realisasi->project->nama }}</td>
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
                                    <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">Belum ada transaksi realisasi untuk akun ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->akunRealisations->sum('nominal')) }}</td>
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
