<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Settlement History') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Riwayat pelunasan AR/AP. Pembatalan/koreksi wajib persetujuan admin.</p>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this payment? The paid amount will be reverted (deducted).">
            <x-bulk-actions :paginator="$this->payments" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="jenis_filter" :value="__('Filter Type')" />
                            <select id="jenis_filter" wire:model.live="jenisFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All</option>
                                @foreach ($this->jenisOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="status_filter" :value="__('Filter Status')" />
                            <select id="status_filter" wire:model.live="statusFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All</option>
                                @foreach ($this->statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($this->payments->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No settlements yet. Use the "Pay" button on the Receivable or Payable page.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">For</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->payments as $payment)
                                    <tr class="hover:bg-gray-50">
<td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $payment->id }})" @checked(in_array($payment->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>

                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">{{ $payment->tanggal->format('d M Y') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $payment->jenis->value === 'masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $payment->jenis->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            @if ($payment->receivable)
                                                Receivable: {{ $payment->receivable->project->kode }}
                                            @elseif ($payment->payable)
                                                Payable: {{ $payment->payable->pihak }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $payment->keterangan }}</td>
                                        <td class="px-6 py-4 text-right font-medium {{ $payment->jenis->value === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $payment->jenis->value === 'masuk' ? '+' : '-' }}{{ format_idr($payment->nominal) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match ($payment->status->value) {
                                                'pending_cancel' => 'bg-amber-100 text-amber-700',
                                                'cancelled' => 'bg-gray-200 text-gray-600',
                                                default => 'bg-green-100 text-green-700',
                                            } }}">
                                                {{ $payment->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if ($payment->status->value === 'active')
                                                    <button type="button" wire:click="requestVoid({{ $payment->id }})" title="Request Cancellation"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                        <x-icon name="x-mark" class="h-4 w-4" />
                                                    </button>
                                                @elseif ($payment->status->value === 'pending_cancel' && auth()->user()->isAdmin())
                                                    <button type="button" wire:click="approveVoid({{ $payment->id }})" title="Approve Cancellation"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-600">
                                                        <x-icon name="check" class="h-4 w-4" />
                                                    </button>
                                                    <button type="button" wire:click="rejectVoid({{ $payment->id }})" title="Reject Cancellation"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">
                                                        <x-icon name="x-mark" class="h-4 w-4" />
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->payments" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>

    {{-- Request cancellation modal --}}
    @if ($this->voidingId !== null)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="$set('voidingId', null)"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">Request Cancellation</h3>
                        <p class="mt-1 text-sm text-gray-500">Alasan pembatalan akan direview admin sebelum diproses.</p>
                        <textarea wire:model="voidReason" rows="3" placeholder="Alasan pembatalan..." class="mt-4 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <x-input-error :messages="$errors->get('voidReason')" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                        <button type="button" wire:click="$set('voidingId', null)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="confirmVoid" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Request Cancellation</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reject cancellation modal --}}
    @if ($this->rejectingId !== null)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="$set('rejectingId', null)"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">Reject Cancellation</h3>
                        <p class="mt-1 text-sm text-gray-500">Catatan penolakan (wajib diisi).</p>
                        <textarea wire:model="rejectNote" rows="3" placeholder="Catatan penolakan..." class="mt-4 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                        <button type="button" wire:click="$set('rejectingId', null)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="confirmReject" class="inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Reject Cancellation</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>