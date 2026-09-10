<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $jenis === 'masuk' ? __('Edit Cash In') : __('Edit Cash Out') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">Perbarui rincian transaksi kas. Perubahan nominal atau tanggal akan otomatis menyesuaikan voucher terkait.</p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $jenis === 'masuk' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $jenis === 'masuk' ? 'Cash In' : 'Cash Out' }}
            </span>
        </div>

        <form wire:submit="save" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
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
                <div>
                    <x-input-label for="project_id" :value="__('Project')" />
                    <select id="project_id" wire:model="projectId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">No Project (Non-Project)</option>
                        @foreach ($this->projects() as $project)
                            <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="pihak_type_id" :value="__('Pihak (Vendor/Supplier/Mandor/Investor)')" />
                    <select id="pihak_type_id" wire:model.live="pihakTypeId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Tipe --</option>
                        @foreach ($this->pihakTypes() as $type)
                            <option value="{{ $type->id }}">{{ $type->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pihak_type_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="pihak_item_id" :value="__('Nama Pihak')" />
                    <select id="pihak_item_id" wire:model="pihakItemId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @if(!$this->pihakTypeId) disabled @endif>
                        <option value="">-- Pilih Tipe Terlebih Dahulu --</option>
                        @foreach ($this->pihakItems() as $item)
                            <option value="{{ $item->id }}">{{ $item->kode }} - {{ $item->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('pihak_item_id')" class="mt-2" />
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
                <x-primary-button wire:loading.attr="disabled" wire:target="save">Simpan Perubahan</x-primary-button>
                <a href="{{ route('cashflows.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Batal</a>
            </div>
        </form>
    </div>
</div>
