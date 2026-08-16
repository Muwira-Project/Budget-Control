<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Add Cash Entry') }}</h2>
        <p class="mt-1 text-sm text-gray-500">Cash In: pemasukan (mis. investor / piutang project selesai). Cash Out: pengeluaran lain (mis. sewa, utility). Settlement AR/AP dicatat lewat menu AR &amp; AP.</p>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="mb-4 flex items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="radio" wire:model="jenis" value="masuk" class="rounded-full border-gray-300 text-green-600 focus:ring-green-500" />
                    Cash In
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="radio" wire:model="jenis" value="keluar" class="rounded-full border-gray-300 text-red-600 focus:ring-red-500" />
                    Cash Out
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="tanggal" :value="__('Date')" />
                    <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" />
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nominal" :value="__('Amount')" />
                    <x-text-input id="nominal" class="mt-1 block w-full" type="number" step="0.01" min="0" wire:model="nominal" />
                    <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
                </div>
                @if ($jenis === 'keluar')
                <div class="sm:col-span-2">
                    <x-input-label for="akun_id" :value="__('Expense Account (COA)')" />
                    <select id="akun_id" wire:model="akunId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Account</option>
                        @foreach ($this->akuns() as $akun)
                            <option value="{{ $akun->id }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('akun_id')" class="mt-2" />
                </div>
                @endif
                <div>
                    <x-input-label for="cash_account_id" :value="__('Lokasi Dana (Rekening)')" />
                    <select id="cash_account_id" wire:model="cashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Unassigned</option>
                        @foreach ($this->cashAccounts() as $account)
                            <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" :value="__('Description')" />
                    <textarea id="keterangan" wire:model="keterangan" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Save</x-primary-button>
                <a href="{{ route('cashflows.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</div>