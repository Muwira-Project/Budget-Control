@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'app-card overflow-hidden']) }}>
    @if ($title || $description || isset($actions))
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if ($title)
                    <h3 class="flex items-center gap-2 font-semibold tracking-tight text-slate-900">
                        @if ($icon)
                            <x-icon :name="$icon" class="h-5 w-5 text-brand-600" />
                        @endif
                        {{ $title }}
                    </h3>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif
    <div @if ($padding) class="p-5" @endif>
        {{ $slot }}
    </div>
</div>