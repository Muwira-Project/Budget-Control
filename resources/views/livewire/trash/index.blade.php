<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trash</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($this->tabs as $key => $label)
                    <button
                        wire:click="setTab('{{ $key }}')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === $key ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Label</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Detail</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Deleted At</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($this->rows as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-800">
                                        {{ $this->rowLabel($row) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $this->rowDetail($row) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
                                        {{ $row->deleted_at?->format('d M Y H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <button
                                            wire:click="restore({{ $row->id }})"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Restore
                                        </button>
                                        <button
                                            wire:click="forceDelete({{ $row->id }})"
                                            wire:confirm="Hapus permanen data ini? Tindakan tidak bisa dibatalkan."
                                            class="ml-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700"
                                        >
                                            Delete Permanently
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                        Trash kosong untuk tab ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
