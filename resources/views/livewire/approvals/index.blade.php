<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-page-header icon="check-circle" title="Approval Center" description="Semua permintaan menunggu persetujuan admin: kegiatan kas, fund transfer, dan pembatalan settlement." />

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        @if (! $this->hasPending && $this->approvedItems->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-200">
                <x-icon name="check-circle" class="mx-auto h-10 w-10 text-green-500" />
                <p class="mt-3 text-sm font-medium text-gray-700">Tidak ada permintaan yang menunggu persetujuan.</p>
                <p class="mt-1 text-xs text-gray-500">Semua persetujuan sudah selesai.</p>
            </div>
        @else
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Pending Approval</h3>
                </div>

                @if ($this->pendingCashflows->isEmpty() && $this->pendingTransfers->isEmpty() && $this->pendingVoids->isEmpty())
                    <p class="p-6 text-sm text-gray-500">Tidak ada item pending.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Detail</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Date / Requested</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->pendingCashflows as $entry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full {{ $entry->jenis->value === 'masuk' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} px-2 py-0.5 text-xs font-medium">
                                                {{ $entry->jenis->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            {{ $entry->sumber->label() }}
                                            <span class="text-gray-400">|</span> {{ $entry->keterangan }}
                                            @if ($entry->cashAccount)<span class="text-gray-400">|</span> {{ $entry->cashAccount->kode }}@endif
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($entry->nominal) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $entry->tanggal->format('d M Y') }} / {{ $entry->submittedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <button type="button" wire:click="approveCashflow({{ $entry->id }})" title="Approve"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="openReject('cashflow', {{ $entry->id }})" title="Reject"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                <x-icon name="x-mark" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach ($this->pendingTransfers as $transfer)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">Fund Transfer</span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            {{ $transfer->dariCashAccount?->kode }} <x-icon name="arrow-trending-right" class="inline h-4 w-4 text-gray-400" /> {{ $transfer->keCashAccount?->kode }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($transfer->nominal) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $transfer->tanggal->format('d M Y') }} / {{ $transfer->submittedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <button type="button" wire:click="approveTransfer({{ $transfer->id }})" title="Approve"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="openReject('transfer', {{ $transfer->id }})" title="Reject"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                <x-icon name="x-mark" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach ($this->pendingVoids as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Settlement Void</span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            {{ $payment->jenis->label() }}
                                            @if ($payment->receivable)<span class="text-gray-400">|</span> {{ $payment->receivable->project->kode }}@endif
                                            @if ($payment->payable)<span class="text-gray-400">|</span> {{ $payment->payable->pihak }}@endif
                                            <span class="text-gray-400">|</span> <span class="text-gray-500">{{ $payment->void_reason }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($payment->nominal) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $payment->tanggal->format('d M Y') }} / {{ $payment->voidRequestedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <button type="button" wire:click="approveVoid({{ $payment->id }})" title="Approve Cancellation"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="openReject('void', {{ $payment->id }})" title="Reject Cancellation"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                <x-icon name="x-mark" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($this->approvedItems->isNotEmpty())
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Approved - Ready to Post</h3>
                        <p class="mt-0.5 text-xs text-gray-500">Post untuk memasukkan ke buku besar / Cash Activity (dan menerbitkan voucher).</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Detail</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Approved By</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->approvedItems as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $row['type'] === 'cashflow' ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700' }}">
                                                {{ $row['type'] === 'cashflow' ? $row['item']->jenis->label() : 'Fund Transfer' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($row['type'] === 'cashflow')
                                                {{ $row['item']->sumber->label() }} <span class="text-gray-400">|</span> {{ $row['item']->keterangan }}
                                            @else
                                                {{ $row['item']->dariCashAccount?->kode }} <x-icon name="arrow-trending-right" class="inline h-4 w-4 text-gray-400" /> {{ $row['item']->keCashAccount?->kode }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">{{ format_idr($row['item']->nominal) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $row['item']->approvedBy?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @if ($row['type'] === 'cashflow')
                                                <button type="button" wire:click="postCashflow({{ $row['item']->id }})" title="Post"
                                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Post</button>
                                                <button type="button" wire:click="openReject('cashflow', {{ $row['item']->id }})" title="Reject"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                    <x-icon name="x-mark" class="h-4 w-4" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="postTransfer({{ $row['item']->id }})" title="Post"
                                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Post</button>
                                                <button type="button" wire:click="openReject('transfer', {{ $row['item']->id }})" title="Reject"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                    <x-icon name="x-mark" class="h-4 w-4" />
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if ($this->rejectingType !== null)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="$set('rejectingType', null)"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">Reject Request</h3>
                        <p class="mt-1 text-sm text-gray-500">Catatan penolakan (wajib diisi).</p>
                        <textarea wire:model="rejectNote" rows="3" placeholder="Alasan penolakan..." class="mt-4 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                        <button type="button" wire:click="$set('rejectingType', null)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="confirmReject" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Reject</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
