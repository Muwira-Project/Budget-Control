@props([
    'label' => '',
    'icon' => null,
    'active' => false,
])

<div
    x-data="{
        isOpen: @js($active),
        flyoutStyle: '',
        init() {
            if (this.collapsed) this.isOpen = false;
        },
        toggleMenu() {
            this.isOpen = !this.isOpen;

            if (!this.isOpen || !this.collapsed) return;

            this.$nextTick(() => {
                const trigger = this.$refs.trigger;
                const flyout = this.$refs.flyout;
                if (!trigger || !flyout) return;

                const rect = trigger.getBoundingClientRect();
                const maxTop = Math.max(8, window.innerHeight - flyout.offsetHeight - 8);
                const top = Math.min(Math.max(8, rect.top), maxTop);
                this.flyoutStyle = `left: ${Math.round(rect.right + 12)}px; top: ${Math.round(top)}px;`;
            });
        },
    }"
    @click.outside="isOpen = false"
    @keydown.escape.window="isOpen = false"
    @scroll.window="if (collapsed) isOpen = false"
    {{ $attributes }}
>
    <button
        x-ref="trigger"
        type="button"
        @click="toggleMenu()"
        :aria-expanded="isOpen ? 'true' : 'false'"
        aria-haspopup="true"
        :title="collapsed ? '{{ $label }}' : ''"
        @class([
            'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition',
            'font-semibold text-white' => $active,
            'font-medium text-slate-300 hover:bg-white/5 hover:text-white' => ! $active,
        ])
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif
        <span class="flex-1 truncate text-start whitespace-nowrap" x-show="!collapsed" x-transition.opacity>{{ $label }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform" x-bind:class="isOpen ? 'rotate-180' : ''" x-show="!collapsed" />
    </button>

    <div x-show="isOpen && !collapsed" x-cloak class="mt-1 space-y-0.5 border-l border-white/10 ps-3 ms-4">
        {{ $slot }}
    </div>

    <div
        x-ref="flyout"
        x-show="isOpen && collapsed"
        x-cloak
        x-transition.opacity
        :style="flyoutStyle"
        @click="isOpen = false"
        class="fixed z-[60] max-h-[calc(100vh_-_1rem)] w-60 overflow-y-auto rounded-xl border border-white/10 bg-brand-950 p-3 shadow-2xl ring-1 ring-black/10"
    >
        <div x-data="{ collapsed: false }">
            <div class="mb-2 px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</div>
            <div class="space-y-0.5 border-l border-white/10 ps-3 ms-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
