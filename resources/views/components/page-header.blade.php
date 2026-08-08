@props([
    'title' => '',
    'description' => null,
    'icon' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <div class="flex items-center gap-2.5">
            @if ($icon)
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
            @endif
            <h2 class="page-title">{{ $title }}</h2>
        </div>
        @if ($description)
            <p class="page-description">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>