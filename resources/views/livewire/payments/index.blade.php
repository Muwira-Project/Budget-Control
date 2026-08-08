<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Payment') }}</h2>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this payment? The paid amount will be reverted (deducted).">
            <x-bulk-actions :paginator="$this->payments" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="jenis_filter" :value="__('Filter Type')" />
                        <select id="jenis_filter" wire:model.live="jenisFilter" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All</option>
                            @foreach ($this->jenisOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($this->payments->isEmpty())
                    <p class="p-6 text-sm text-gray-500">No payments yet. Use the "Pay" button on the Receivable or Payable page.</p>
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
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :delete-id="$payment->id" />
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
</div>
