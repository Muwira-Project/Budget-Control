<div class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Import Cash Accounts</h2>
            <a href="{{ route('imports.cash-accounts.template') }}" class="inline-flex items-center rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600">Download Template</a>
        </div>
        <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <form wire:submit="import" class="p-6">
                <p class="text-sm text-gray-600">Use Type <code>kas</code> or <code>bank</code>, Default <code>yes</code>/<code>no</code>, and Status <code>active</code>/<code>inactive</code>. Existing account codes are skipped with an error; import does not overwrite accounts.</p>
                <input type="file" wire:model="file" accept=".xlsx,.xls" class="mt-4 block w-full text-sm" />
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <div class="mt-6"><x-primary-button wire:loading.attr="disabled" wire:target="import">Import</x-primary-button></div>
            </form>
        </div>
        @include('livewire.imports._report', ['report' => $report])
    </div>
</div>
