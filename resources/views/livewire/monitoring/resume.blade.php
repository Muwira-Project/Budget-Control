<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="scale" title="Period Resume" description="Per-account budget, actual, and variance for this monitoring period.">
            <x-slot:actions>
                @if (auth()->user()->isAdmin())
                <div x-data="{ exportOpen: false }" @click.outside="exportOpen = false" class="relative">
                    <button type="button" @click="exportOpen = ! exportOpen" class="inline-flex items-center gap-2 rounded-lg border border-brand-600 bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none">
                        <x-icon name="download" class="h-4 w-4 text-white" />
                        <span>Export Report</span>
                        <x-icon name="chevron-down" class="h-3.5 w-3.5 text-white/80 transition-transform" x-bind:class="exportOpen ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="exportOpen" x-cloak class="absolute right-0 z-50 mt-1.5 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('exports.monitoring-variance-detail', ['period' => $monitoringPeriod, 'format' => 'xlsx']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-blue-700 bg-blue-50/50 hover:bg-blue-50">
                            <x-icon name="document-text" class="h-4 w-4 text-blue-600" />
                            <span>Detail XLSX (Per Transaksi)</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="{{ route('exports.monitoring-variance', ['period' => $monitoringPeriod, 'format' => 'xlsx']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            <span class="font-bold text-emerald-600">XLSX</span> Variance Summary
                        </a>
                        <a href="{{ route('exports.monitoring-variance', ['period' => $monitoringPeriod, 'format' => 'csv']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            <span class="font-bold text-blue-600">CSV</span> Data Format
                        </a>
                        <a href="{{ route('exports.monitoring-variance', ['period' => $monitoringPeriod, 'format' => 'pdf']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            <span class="font-bold text-rose-600">PDF</span> Document
                        </a>
                    </div>
                </div>
                @endif
            </x-slot:actions>
        </x-page-header>

        <p class="text-sm text-slate-500">
            {{ $monitoringPeriod->nomor }}
            · {{ $monitoringPeriod->budget_number ?? 'No Budget Number' }}
            @if ($monitoringPeriod->project)
            · {{ $monitoringPeriod->project->kode }} - {{ $monitoringPeriod->project->nama }}
            @else
            · All Projects (Global)
            @endif
            · Week {{ $monitoringPeriod->week }} · {{ $monitoringPeriod->month }} (Accrual basis)
        </p>

        <a href="{{ route('monitoring.index') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">Back to Monitoring</a>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Budget</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_idr($this->totals['budget']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Actual In</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ format_idr($this->totals['actual_in']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Actual Out</p>
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
                                    @if ($realisasi->pihakJenis && $realisasi->pihak)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ ucfirst($realisasi->pihakJenis) }}</span> {{ $realisasi->pihak }}
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