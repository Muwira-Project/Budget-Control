<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Period Resume') }} — {{ $monitoringPeriod->nomor }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $monitoringPeriod->periode_label }}
                    @if ($monitoringPeriod->project)
                        · {{ $monitoringPeriod->project->kode }} - {{ $monitoringPeriod->project->nama }}
                    @else
                        · All Projects (Global)
                    @endif
                    · Week {{ $monitoringPeriod->week }} · {{ $monitoringPeriod->month }}
                </p>
            </div>
            <a href="{{ route('monitoring.index') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">Back to Monitoring</a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Budget</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_idr($this->totals['budget']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Actual</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_idr($this->totals['actual']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Variance</p>
                <p class="mt-2 text-2xl font-bold {{ $this->totals['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($this->totals['variance']) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="font-semibold text-gray-800">Rincian Pos Akun</h3>
                <p class="mt-0.5 text-xs text-slate-500">Klik nominal Actual pada baris akun untuk melihat rincian transaksi realisasi.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Account</th>
                            <th class="px-6 py-3 text-right">Budget</th>
                            <th class="px-6 py-3 text-right">Actual</th>
                            <th class="px-6 py-3 text-right">Variance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($this->breakdown as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900">{{ $row['akun']->kode_akun }}</span>
                                    <span class="text-gray-500"> - {{ $row['akun']->nama_akun }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900">{{ format_idr($row['budget']) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button"
                                        wire:click="showAccountDetail({{ $row['akun']->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 font-medium text-blue-600 transition hover:bg-blue-50 hover:text-blue-800">
                                        {{ format_idr($row['actual']) }}
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right font-medium {{ $row['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($row['variance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-sm text-gray-500">No budget or actual data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ format_idr($this->totals['budget']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ format_idr($this->totals['actual']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold {{ $this->totals['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_idr($this->totals['variance']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($this->selectedAkunId !== null && $this->selectedAkun)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-cloak>
            <div class="fixed inset-0 bg-gray-900/50" wire:click="closeAccountDetail"></div>
            <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Rincian Realisasi — {{ $this->selectedAkun->kode_akun }} {{ $this->selectedAkun->nama_akun }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Periode {{ $monitoringPeriod->periode_label }}</p>
                    </div>
                    <button type="button" wire:click="closeAccountDetail" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Pihak</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($this->accountRealisations as $realisasi)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($realisasi->pihakJenis === 'vendor' && $realisasi->vendor)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Vendor</span> {{ $realisasi->vendor->nama }}
                                        @elseif ($realisasi->pihakJenis === 'supplier' && $realisasi->supplier)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Supplier</span> {{ $realisasi->supplier->nama }}
                                        @elseif ($realisasi->pihakJenis === 'mandor' && $realisasi->mandor)
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">Mandor</span> {{ $realisasi->mandor->nama }}
                                        @elseif ($realisasi->pihakJenis === 'investor' && $realisasi->investor)
                                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Investor</span> {{ $realisasi->investor->nama }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($realisasi->kategori)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $realisasi->kategori->nama }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">{{ $realisasi->keterangan }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($realisasi->nominal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada transaksi realisasi untuk pos akun ini pada periode tersebut.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-4 font-semibold text-gray-900">Total Realisasi</td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ format_idr($this->accountRealisations->sum('nominal')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>