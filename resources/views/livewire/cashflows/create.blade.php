<div class="py-8">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">Tambah Transaksi Kas</h2>
            <p class="mt-1 text-sm text-slate-500">Catat pemasukan / pengeluaran kas, atau lunasi invoice AR & AP yang sudah ada.</p>
        </div>

        <form wire:submit="save" class="space-y-5">

            {{-- ══════════════════════════════════════════════════════
                 STEP 1 — Arah Kas
            ══════════════════════════════════════════════════════ --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Arah Kas</p>
                <div class="flex gap-3">
                    <label for="jenis_masuk"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $jenis === 'masuk' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white hover:border-emerald-300' }}">
                        <input id="jenis_masuk" type="radio" wire:model.live="jenis" value="masuk" class="accent-emerald-600" />
                        <div>
                            <p class="font-semibold text-emerald-700">Cash In</p>
                            <p class="text-xs text-slate-500">Pemasukan / penerimaan</p>
                        </div>
                    </label>
                    <label for="jenis_keluar"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $jenis === 'keluar' ? 'border-red-500 bg-red-50' : 'border-slate-200 bg-white hover:border-red-300' }}">
                        <input id="jenis_keluar" type="radio" wire:model.live="jenis" value="keluar" class="accent-red-600" />
                        <div>
                            <p class="font-semibold text-red-700">Cash Out</p>
                            <p class="text-xs text-slate-500">Pengeluaran / pembayaran</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 STEP 2 — Tipe Transaksi
            ══════════════════════════════════════════════════════ --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Tipe Transaksi</p>
                <div class="flex gap-3">
                    <label for="scope_project"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $scope === 'project' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 bg-white hover:border-brand-300' }}">
                        <input id="scope_project" type="radio" wire:model.live="scope" value="project" class="accent-blue-600" />
                        <div>
                            <p class="font-semibold text-slate-800">Project</p>
                            <p class="text-xs text-slate-500">Terkait project — langsung lunasi invoice AR/AP</p>
                        </div>
                    </label>
                    <label for="scope_non_project"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $scope === 'non_project' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 bg-white hover:border-brand-300' }}">
                        <input id="scope_non_project" type="radio" wire:model.live="scope" value="non_project" class="accent-blue-600" />
                        <div>
                            <p class="font-semibold text-slate-800">Non-Project</p>
                            <p class="text-xs text-slate-500">Umum — kas langsung atau via invoice AR/AP</p>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('scope')" class="mt-2" />
            </div>

            {{-- ══════════════════════════════════════════════════════
                 STEP 3A — Pilih Project (jika scope = project)
            ══════════════════════════════════════════════════════ --}}
            @if ($scope === 'project')
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Project</p>
                <select id="project_id" wire:model.live="projectId"
                    class="block w-full rounded-lg border-slate-300 bg-slate-50 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Pilih Project —</option>
                    @foreach ($this->projects() as $project)
                        <option value="{{ $project->id }}">{{ $project->kode }} — {{ $project->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('projectId')" class="mt-2" />
            </div>
            @endif

            {{-- ══════════════════════════════════════════════════════
                 STEP 3B — Metode (jika scope = non_project)
            ══════════════════════════════════════════════════════ --}}
            @if ($scope === 'non_project')
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Metode Pencatatan</p>
                <div class="flex gap-3">
                    <label for="link_ar_ap"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $linkMode === 'ar_ap' ? 'border-violet-500 bg-violet-50' : 'border-slate-200 bg-white hover:border-violet-300' }}">
                        <input id="link_ar_ap" type="radio" wire:model.live="linkMode" value="ar_ap" class="accent-violet-600" />
                        <div>
                            <p class="font-semibold text-slate-800">Linked AR/AP Invoice</p>
                            <p class="text-xs text-slate-500">Pilih invoice yang sudah ada, saldo AR/AP otomatis berkurang</p>
                        </div>
                    </label>
                    <label for="link_direct"
                        class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition
                               {{ $linkMode === 'direct' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-400' }}">
                        <input id="link_direct" type="radio" wire:model.live="linkMode" value="direct" class="accent-slate-700" />
                        <div>
                            <p class="font-semibold text-slate-800">Kas Langsung</p>
                            <p class="text-xs text-slate-500">Tanpa AR/AP — catat kas manual biasa</p>
                        </div>
                    </label>
                </div>
            </div>
            @endif

            {{-- ══════════════════════════════════════════════════════
                 STEP 4 — Invoice AR/AP (jika AR/AP mode aktif)
            ══════════════════════════════════════════════════════ --}}
            @if ($this->isLinkedArAp())
                @if ($scope === 'project' && ! $projectId)
                    {{-- Hint: pilih project dulu --}}
                    <div class="flex items-center gap-3 rounded-2xl border border-dashed border-blue-300 bg-blue-50 p-4 text-sm text-blue-700">
                        <x-icon name="information-circle" class="h-5 w-5 shrink-0 text-blue-500" />
                        Pilih project terlebih dahulu untuk melihat daftar invoice.
                    </div>
                @else
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        @if ($jenis === 'masuk')
                            {{-- AR Invoice selector --}}
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">Invoice AR (Piutang)</p>
                            <p class="mb-3 text-xs text-slate-400">Hanya menampilkan invoice yang belum lunas.</p>
                            <select id="receivable_id" wire:model.live="receivableId"
                                class="block w-full rounded-lg border-slate-300 bg-slate-50 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— Pilih Invoice AR —</option>
                                @forelse ($this->outstandingReceivables() as $ar)
                                    <option value="{{ $ar->id }}">
                                        {{ $ar->nomor_invoice ?? 'INV#'.$ar->id }}
                                        @if ($ar->project) · {{ $ar->project->kode }} @endif
                                        · Sisa: Rp {{ number_format($ar->sisa, 0, ',', '.') }}
                                        @if ($ar->jatuh_tempo) · Jatuh tempo: {{ $ar->jatuh_tempo->format('d/m/Y') }} @endif
                                    </option>
                                @empty
                                    <option disabled value="">Tidak ada invoice AR outstanding</option>
                                @endforelse
                            </select>
                            <x-input-error :messages="$errors->get('receivableId')" class="mt-2" />

                            @if ($receivableId)
                                @php $selAr = $this->outstandingReceivables()->firstWhere('id', $receivableId); @endphp
                                @if ($selAr)
                                <div class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm ring-1 ring-emerald-200">
                                    <p class="font-medium text-emerald-800">{{ $selAr->nomor_invoice ?? 'Invoice #'.$selAr->id }}</p>
                                    <div class="mt-1 flex gap-4 text-xs text-emerald-700">
                                        <span>Total: <strong>Rp {{ number_format($selAr->nominal, 0, ',', '.') }}</strong></span>
                                        <span>Dibayar: <strong>Rp {{ number_format($selAr->nominal_dibayar, 0, ',', '.') }}</strong></span>
                                        <span>Sisa: <strong>Rp {{ number_format($selAr->sisa, 0, ',', '.') }}</strong></span>
                                    </div>
                                </div>
                                @endif
                            @endif
                        @else
                            {{-- AP Invoice selector --}}
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">Invoice AP (Hutang)</p>
                            <p class="mb-3 text-xs text-slate-400">Hanya menampilkan invoice yang belum lunas.</p>
                            <select id="payable_id" wire:model.live="payableId"
                                class="block w-full rounded-lg border-slate-300 bg-slate-50 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="">— Pilih Invoice AP —</option>
                                @forelse ($this->outstandingPayables() as $ap)
                                    <option value="{{ $ap->id }}">
                                        {{ $ap->nomor_invoice ?? 'INV#'.$ap->id }}
                                        @if ($ap->pihakItem) · {{ $ap->pihakItem->nama }} @endif
                                        @if ($ap->project) · {{ $ap->project->kode }} @endif
                                        · Sisa: Rp {{ number_format($ap->sisa, 0, ',', '.') }}
                                        @if ($ap->jatuh_tempo) · Jatuh tempo: {{ $ap->jatuh_tempo->format('d/m/Y') }} @endif
                                    </option>
                                @empty
                                    <option disabled value="">Tidak ada invoice AP outstanding</option>
                                @endforelse
                            </select>
                            <x-input-error :messages="$errors->get('payableId')" class="mt-2" />

                            @if ($payableId)
                                @php $selAp = $this->outstandingPayables()->firstWhere('id', $payableId); @endphp
                                @if ($selAp)
                                <div class="mt-3 rounded-lg bg-red-50 p-3 text-sm ring-1 ring-red-200">
                                    <p class="font-medium text-red-800">{{ $selAp->nomor_invoice ?? 'Invoice #'.$selAp->id }}
                                        @if ($selAp->pihakItem) — {{ $selAp->pihakItem->nama }} @endif
                                    </p>
                                    <div class="mt-1 flex gap-4 text-xs text-red-700">
                                        <span>Total: <strong>Rp {{ number_format($selAp->nominal, 0, ',', '.') }}</strong></span>
                                        <span>Dibayar: <strong>Rp {{ number_format($selAp->nominal_dibayar, 0, ',', '.') }}</strong></span>
                                        <span>Sisa: <strong>Rp {{ number_format($selAp->sisa, 0, ',', '.') }}</strong></span>
                                    </div>
                                </div>
                                @endif
                            @endif
                        @endif
                    </div>
                @endif
            @endif

            {{-- ══════════════════════════════════════════════════════
                 STEP 4B — Fields for Direct (manual) mode
            ══════════════════════════════════════════════════════ --}}
            @if ($scope === 'non_project' && $linkMode === 'direct')
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pihak (Opsional)</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="pihak_type_id" :value="__('Tipe Pihak')" />
                        <select id="pihak_type_id" wire:model.live="pihakTypeId"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Pilih Tipe —</option>
                            @foreach ($this->pihakTypes() as $type)
                                <option value="{{ $type->id }}">{{ $type->nama }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pihak_type_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="pihak_item_id" :value="__('Nama Pihak')" />
                        <select id="pihak_item_id" wire:model="pihakItemId"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @if(! $pihakTypeId) disabled @endif>
                            <option value="">— Pilih Pihak —</option>
                            @foreach ($this->pihakItems() as $item)
                                <option value="{{ $item->id }}">{{ $item->kode }} — {{ $item->nama }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pihak_item_id')" class="mt-2" />
                    </div>

                    @if ($jenis === 'keluar')
                    <div class="sm:col-span-2">
                        <x-input-label for="akun_id" :value="__('Akun Pengeluaran (COA)')" />
                        <select id="akun_id" wire:model="akunId"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Pilih Akun —</option>
                            @foreach ($this->akuns() as $akun)
                                <option value="{{ $akun->id }}">{{ $akun->kode_akun }} — {{ $akun->nama_akun }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('akun_id')" class="mt-2" />
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ══════════════════════════════════════════════════════
                 STEP 5 — Nominal, Tanggal, Rekening, Keterangan
                          (tampil setelah scope & mode dipilih)
            ══════════════════════════════════════════════════════ --}}
            @if ($scope && ($scope === 'project' || $linkMode))
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Detail Transaksi</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="tanggal" :value="__('Tanggal')" />
                        <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" />
                        <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="nominal" :value="__('Nominal (Rp)')" />
                        <div class="relative mt-1">
                            <x-text-input id="nominal" class="block w-full pl-10" type="number" step="0.01" min="0.01"
                                wire:model="nominal"
                                placeholder="{{ $this->isLinkedArAp() ? 'Auto-filled dari sisa invoice' : '0' }}" />
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">Rp</span>
                        </div>
                        @if ($this->isLinkedArAp())
                            <p class="mt-1 text-xs text-slate-400">Boleh diubah untuk partial payment.</p>
                        @endif
                        <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="cash_account_id" :value="__('Rekening Kas')" />
                        <select id="cash_account_id" wire:model="cashAccountId"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Pilih Rekening —</option>
                            @foreach ($this->cashAccounts() as $account)
                                <option value="{{ $account->id }}">{{ $account->kode }} — {{ $account->nama }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('cashAccountId')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="keterangan" :value="__('Keterangan (Opsional)')" />
                        <x-text-input id="keterangan" class="mt-1 block w-full" type="text" wire:model="keterangan"
                            placeholder="Catatan tambahan..." />
                        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pb-4">
                <x-primary-button wire:loading.attr="disabled" wire:target="save"
                    class="{{ $jenis === 'masuk' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }}">
                    <span wire:loading.remove wire:target="save">
                        {{ $this->isLinkedArAp() ? ($jenis === 'masuk' ? 'Catat Penerimaan AR' : 'Catat Pembayaran AP') : 'Simpan' }}
                    </span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </x-primary-button>
                <a href="{{ route('cashflows.index') }}" wire:navigate
                    class="text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
            </div>
            @endif

        </form>
    </div>
</div>