<div class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Add Non-Project Expense') }}</h2>

        <div class="mt-6 overflow-hidden bg-white shadow-sm rounded-lg">
            <form wire:submit="save" class="p-6">
                @include('livewire.non-project-expenses._form')

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('non-project-expenses.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>