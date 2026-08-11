<div class="py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Actual Detail') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Detail transaksi realisasi / rincian akun.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('realisasi.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Back to Actual</a>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <dl class="divide-y divide-gray-100">
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal</dt>
                    <dd class="text-gray-900">{{ $realisasi->tanggal->format('d M Y') }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Akun</dt>
                    <dd class="text-gray-900">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Project</dt>
                    <dd class="text-gray-900">{{ $realisasi->project->kode }} - {{ $realisasi->project->nama }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pihak</dt>
                    <dd class="text-gray-900">
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
                    </dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</dt>
                    <dd class="text-gray-900">
                        @if ($realisasi->kategori)
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $realisasi->kategori->nama }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nominal</dt>
                    <dd class="text-lg font-bold text-gray-900">{{ format_idr($realisasi->nominal) }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 sm:gap-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Keterangan</dt>
                    <dd class="text-gray-700">{{ $realisasi->keterangan ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
