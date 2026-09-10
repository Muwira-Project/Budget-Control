<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Fund Transfer') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Pemindahan dana antar rekening (bukan pendapatan/beban).</p>
            </div>
            <a href="{{ route('fund-transfers.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add Fund Transfer
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this fund transfer?">
            <x-bulk-actions :paginator="$this->transfers" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                @if ($this->transfers->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No fund transfers yet. Click "Add Fund Transfer".</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">From</th>
                                    <th class="px-6 py-3">To</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->transfers as $transfer)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $transfer->id }})" @checked(in_array($transfer->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>

                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $transfer->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($transfer->status->value) {
                                                'posted' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-200 text-gray-600',
                                            } }}">
                                                {{ $transfer->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $transfer->dariCashAccount?->kode }} - {{ $transfer->dariCashAccount?->nama }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $transfer->keCashAccount?->kode }} - {{ $transfer->keCashAccount?->nama }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($transfer->nominal) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $transfer->keterangan }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="{{ route('fund-transfers.edit', $transfer) }}" wire:navigate title="Edit Transfer" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-blue-600 shadow-sm transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700">
                                                    <x-icon name="edit" class="h-4 w-4" />
                                                </a>
                                                @if ($transfer->status->value === 'posted')
                                                    <button type="button" onclick="openPrintPreview('{{ route('fund-transfers.print', $transfer) }}')" title="Cetak Voucher" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                                                        <x-icon name="printer" class="h-4 w-4" />
                                                    </button>
                                                @endif
                                                @if (! $transfer->isPosted())
                                                    <x-action-buttons :delete-id="$transfer->id" />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->transfers" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>

@push('scripts')
<script>
    function openPrintPreview(url) {
        const win = window.open(url, '_blank', 'width=800,height=900');
        if (win) {
            win.onload = function() {
                win.print();
            };
        }
    }
</script>
@endpush