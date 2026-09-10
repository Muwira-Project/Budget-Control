<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Edit Fund Transfer') }}</h2>
        <p class="mt-1 text-sm text-gray-500">Perbarui rincian transfer antar rekening kas / bank. Perubahan nominal atau tanggal akan otomatis menyesuaikan voucher terkait.</p>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="tanggal" :value="__('Date')" />
                    <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" />
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nominal" :value="__('Amount')" />
                    <x-text-input id="nominal" class="mt-1 block w-full" type="number" step="0.01" min="1" wire:model="nominal" />
                    <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="dari" :value="__('From Account')" />
                    <select id="dari" wire:model="dariCashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select...</option>
                        @foreach ($this->cashAccounts() as $account)
                            <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('dari_cash_account_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="ke" :value="__('To Account')" />
                    <select id="ke" wire:model="keCashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select...</option>
                        @foreach ($this->cashAccounts() as $account)
                            <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('ke_cash_account_id')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" :value="__('Description')" />
                    <textarea id="keterangan" wire:model="keterangan" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Simpan Perubahan</x-primary-button>
                <a href="{{ route('fund-transfers.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Batal</a>
            </div>
        </form>
    </div>
</div>
