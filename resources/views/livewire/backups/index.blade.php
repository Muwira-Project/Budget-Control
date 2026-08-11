<div class="py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Backup</h2>
                <p class="mt-1 text-sm text-gray-500">Cadangkan database aplikasi dan unduh hasilnya. Halaman ini khusus admin.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button" wire:click="createBackup" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <x-icon name="arrow-path" class="mr-2 h-4 w-4" />
                    <span wire:loading.remove wire:target="createBackup">Buat Backup</span>
                    <span wire:loading wire:target="createBackup">Membuat...</span>
                </button>
                <button type="button" wire:click="cleanOldBackups" wire:confirm="Hapus backup lama sesuai kebijakan retensi?"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50">
                    Bersihkan Backup Lama
                </button>
            </div>
        </div>

        @if ($message)
            <div class="mt-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <x-icon name="check-circle" class="h-5 w-5" /> {{ $message }}
            </div>
        @endif
        @if ($error)
            <div class="mt-6 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <x-icon name="alert" class="h-5 w-5" /> {{ $error }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            @if (empty($this->backups))
                <p class="p-6 text-sm text-gray-500">Belum ada backup. Klik "Buat Backup" untuk membuat backup database pertama.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Nama File</th>
                                <th class="px-6 py-3">Ukuran</th>
                                <th class="px-6 py-3">Dibuat</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($this->backups as $backup)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $backup['name'] }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $backup['size'] }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ \Carbon\Carbon::createFromTimestamp($backup['modified_at'])->format('d M Y H:i') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" wire:click="download('{{ $backup['path'] }}')" title="Unduh"
                                                    class="rounded-lg p-1.5 text-blue-600 hover:bg-blue-50">
                                                <x-icon name="download" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="delete('{{ $backup['path'] }}')" wire:confirm="Yakin ingin menghapus backup ini?" title="Hapus"
                                                    class="rounded-lg p-1.5 text-red-600 hover:bg-red-50">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>