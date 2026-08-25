@props([
    'title' => 'Konfirmasi Delete',
    'message' => 'Are you sure you want to delete this data? This action cannot be undone.',
    'confirmLabel' => 'Delete',
])

<div {{ $attributes }}>
    <div x-data="{ isOpen: false, target: null, openWith(target) { this.target = target; this.isOpen = true; } }">
        {{ $slot }}

        <!-- Modal -->
        <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" @click="isOpen = false"></div>

                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <h3 id="confirm-modal-title" class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                        <button type="button" @click="isOpen = false" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="button" @click="isOpen = false; $wire.delete(target)" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            {{ $confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
