<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50">
                <x-icon name="banknotes" class="h-5 w-5 text-brand-600" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">{{ __('Cash Flow per Account') }}</h2>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Cash Basis</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Pergerakan kas per rekening fisik. Pilih rekening tertentu untuk melihat dan mengekspor buku kas/bank transaksi rinci beserta saldo berjalan.</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="start_date" :value="__('Start Date')" />
                <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
            </div>
            <div>
                <x-input-label for="end_date" :value="__('End Date')" />
                <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
            </div>
            <div>
                <x-input-label for="cash_account_id" :value="__('Pilih Bank / Kas')" />
                <select id="cash_account_id" wire:model.live="cashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Semua Rekening (Ringkasan) --</option>
                    @foreach ($this->cashAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }} ({{ $account->jenis?->label() }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a href="{{ $this->downloadUrl('xlsx') }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Excel (.xlsx) {{ $this->cashAccountId ? '- Detail Transaksi' : '- Ringkasan' }}
            </a>
            <a href="{{ $this->downloadUrl('csv') }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-600 bg-white px-4 py-2 text-sm font-semibold text-green-600 shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV {{ $this->cashAccountId ? '- Detail Transaksi' : '- Ringkasan' }}
            </a>
        </div>

        @if ($this->cashAccountId && $this->detailReport)
            {{-- Detail Bank Metrics --}}
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Saldo Awal</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ format_idr($this->detailReport['saldo_awal']) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Sebelum {{ $this->startDate ?: 'awal transaksi' }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Total Masuk</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">{{ format_idr($this->detailReport['total_masuk']) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Cash in + Transfer masuk</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Total Keluar</p>
                    <p class="mt-1 text-lg font-bold text-red-600">{{ format_idr($this->detailReport['total_keluar']) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Cash out + Transfer keluar</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Saldo Akhir</p>
                    <p class="mt-1 text-lg font-bold text-brand-600">{{ format_idr($this->detailReport['saldo_akhir']) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Per {{ $this->endDate ?: 'saat ini' }}</p>
                </div>
            </div>

            {{-- Detail Bank Transaction Ledger Table --}}
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 bg-gray-50/75 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Buku Kas / Mutasi Transaksi: {{ $this->detailReport['account']['kode'] }} - {{ $this->detailReport['account']['nama'] }}</h3>
                            <p class="text-xs text-gray-500">Daftar transaksi kronologis beserta saldo berjalan (running balance).</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">
                            {{ count($this->detailReport['transactions']) }} Transaksi
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">No. Ref</th>
                                <th class="px-6 py-3">Tipe</th>
                                <th class="px-6 py-3">Sumber / Pihak</th>
                                <th class="px-6 py-3">Keterangan</th>
                                <th class="px-6 py-3 text-right">Penerimaan (Masuk)</th>
                                <th class="px-6 py-3 text-right">Pengeluaran (Keluar)</th>
                                <th class="px-6 py-3 text-right">Saldo Berjalan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            {{-- Baris Saldo Awal --}}
                            <tr class="bg-slate-50 font-medium">
                                <td class="px-6 py-3 text-gray-500">{{ $this->startDate ?: '-' }}</td>
                                <td class="px-6 py-3 text-gray-400">-</td>
                                <td class="px-6 py-3 text-gray-700">
                                    <span class="inline-flex items-center rounded bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">SALDO AWAL</span>
                                </td>
                                <td class="px-6 py-3 text-gray-500">-</td>
                                <td class="px-6 py-3 text-gray-500 italic">Saldo awal sebelum periode</td>
                                <td class="px-6 py-3 text-right text-gray-400">-</td>
                                <td class="px-6 py-3 text-right text-gray-400">-</td>
                                <td class="px-6 py-3 text-right font-bold text-gray-900">{{ format_idr($this->detailReport['saldo_awal']) }}</td>
                            </tr>

                            @forelse ($this->detailReport['transactions'] as $tx)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $tx['tanggal_fmt'] }}</td>
                                    <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ $tx['ref_no'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ in_array($tx['type_code'], ['CF', 'TR_IN'], true) && $tx['masuk'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $tx['jenis'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <span class="font-medium text-gray-800">{{ $tx['sumber'] }}</span>
                                        @if ($tx['pihak'] !== '-')
                                            <span class="block text-xs text-gray-400">{{ $tx['pihak'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $tx['keterangan'] }}</td>
                                    <td class="px-6 py-4 text-right text-emerald-600 font-medium whitespace-nowrap">
                                        {{ $tx['masuk'] > 0 ? format_idr($tx['masuk']) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-rose-600 font-medium whitespace-nowrap">
                                        {{ $tx['keluar'] > 0 ? format_idr($tx['keluar']) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 whitespace-nowrap">
                                        {{ format_idr($tx['saldo_berjalan']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">Tidak ada mutasi transaksi pada rentang periode yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-3 font-semibold text-gray-900">Total Mutasi & Saldo Akhir</td>
                                <td class="px-6 py-3 text-right font-semibold text-emerald-600">{{ format_idr($this->detailReport['total_masuk']) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-red-600">{{ format_idr($this->detailReport['total_keluar']) }}</td>
                                <td class="px-6 py-3 text-right font-bold text-brand-600">{{ format_idr($this->detailReport['saldo_akhir']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            {{-- All Accounts Summary Metrics --}}
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Saldo Awal</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ format_idr($this->report['totals']['saldo_awal']) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Masuk</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">{{ format_idr($this->report['totals']['masuk']) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Keluar</p>
                    <p class="mt-1 text-lg font-bold text-red-600">{{ format_idr($this->report['totals']['keluar']) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Transfer (net)</p>
                    <p class="mt-1 text-lg font-bold text-gray-700">{{ format_idr($this->report['totals']['tr_in'] - $this->report['totals']['tr_out']) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Saldo Akhir</p>
                    <p class="mt-1 text-lg font-bold text-brand-600">{{ format_idr($this->report['totals']['saldo_akhir']) }}</p>
                </div>
            </div>

            {{-- All Accounts Summary Table --}}
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                @if (empty($this->report['rows']))
                    <p class="p-6 text-sm text-gray-500">No cash accounts yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Account</th>
                                    <th class="px-6 py-3 text-right">Saldo Awal</th>
                                    <th class="px-6 py-3 text-right">Masuk</th>
                                    <th class="px-6 py-3 text-right">Keluar</th>
                                    <th class="px-6 py-3 text-right">Transfer In</th>
                                    <th class="px-6 py-3 text-right">Transfer Out</th>
                                    <th class="px-6 py-3 text-right">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->report['rows'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-700">{{ $row['kode'] }} - {{ $row['nama'] }} <span class="text-xs text-gray-400">({{ $row['jenis'] }})</span></td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['saldo_awal']) }}</td>
                                        <td class="px-6 py-4 text-right text-emerald-600">{{ format_idr($row['masuk']) }}</td>
                                        <td class="px-6 py-4 text-right text-red-600">{{ format_idr($row['keluar']) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['tr_in']) }}</td>
                                        <td class="px-6 py-4 text-right text-gray-700">{{ format_idr($row['tr_out']) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['saldo_akhir']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-6 py-3 font-semibold text-gray-900">Total</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['saldo_awal']) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-emerald-600">{{ format_idr($this->report['totals']['masuk']) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-red-600">{{ format_idr($this->report['totals']['keluar']) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['tr_in']) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['tr_out']) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_idr($this->report['totals']['saldo_akhir']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>