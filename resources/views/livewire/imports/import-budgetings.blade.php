<div class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Import Budgeting') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Import alokasi budget untuk Proyek dan Non-Proyek (Overhead, AP, AR) dari file Excel.</p>
            </div>
            <a href="{{ route('imports.budgeting.template') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <x-icon name="download" class="mr-1.5 h-4 w-4" /> Download Template Excel
            </a>
        </div>

        <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <form wire:submit="import" class="p-6">
                <div class="rounded-lg bg-blue-50 p-4 text-xs text-blue-800">
                    <p class="font-semibold text-sm text-blue-900 mb-1">Format Kolom Template Baru:</p>
                    <p class="font-mono text-[11px] bg-white/70 p-1.5 rounded border border-blue-200 text-blue-950">
                        Project Code, Project Name, Budgeting Number, Account Code, Account Name, Type, Party Type, Party Name, Budget, Allocation, Status
                    </p>
                    <ul class="mt-2.5 list-disc pl-5 space-y-1 text-blue-900">
                        <li><strong class="text-blue-950">Budget Proyek</strong>: Isi <code>Project Code</code> (contoh: <code>PRJ-001</code>) dan <code>Account Code</code>. <code>Project Name</code> dan <code>Budgeting Number</code> bersifat opsional untuk dokumentasi.</li>
                        <li><strong class="text-blue-950">Budget Non-Proyek</strong>: Isi <code>Project Code</code> dengan <code>Non-Project</code> (atau biarkan kosong/tanda strip <code>-</code>).</li>
                        <li><strong class="text-blue-950">Pilihan Type</strong>: 
                            <code>other_outcome</code> (Pengeluaran operasional/overhead), 
                            <code>other_income</code> (Pendapatan lain-lain), 
                            <code>ap</code> (Hutang vendor/supplier), 
                            <code>ar</code> (Piutang usaha).
                        </li>
                        <li><strong class="text-blue-950">Party Type & Name</strong>: Untuk <code>ap</code> / <code>ar</code>, isi <code>Party Type</code> (contoh: <code>supplier</code>, <code>vendor</code>, <code>mandor</code>, <code>investor</code>, <code>customer</code>) dan <code>Party Name</code>. Untuk pengeluaran umum, <code>Party Name</code> berfungsi sebagai deskripsi biaya (contoh: <em>Biaya Listrik, Internet & Air</em>).</li>
                        <li><strong class="text-blue-950">Status</strong>: <code>approved</code> (otomatis disetujui), <code>draft</code>, atau <code>waiting</code>.</li>
                    </ul>
                </div>

                <div class="mt-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File Excel (.xlsx / .xls)</label>
                    <input type="file" wire:model="file" accept=".xlsx,.xls" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100" />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
                    <a href="{{ route('budgeting.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Kembali ke Budgeting</a>
                    <x-primary-button wire:loading.attr="disabled" wire:target="import">
                        <span wire:loading.remove wire:target="import">Mulai Import Data</span>
                        <span wire:loading wire:target="import">Memproses Data...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>

        @include('livewire.imports._report', ['report' => $report])
    </div>
</div>
