<?php

use App\Livewire\Actions\Logout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * The latest unread notifications (cached once per render).
     */
    #[Computed]
    public function unreadNotifications()
    {
        return auth()->user()->unreadNotifications()->take(8)->get();
    }

    /**
     * Sidebar collapsed state (persisted in user preferences and localStorage).
     */
    public bool $sidebarCollapsed = false;

    public function mount(): void
    {
        $this->sidebarCollapsed = auth()->user()->dashboard_preferences['sidebar_collapsed'] ?? false;
    }

    /**
     * Mark a single notification as read.
     */
    public function markNotificationAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    /**
     * Mark every notification as read.
     */
    public function markAllNotificationsAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    /**
     * Toggle sidebar collapsed state.
     */
    public function toggleSidebarCollapse(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;

        // Persist to user preferences
        $preferences = auth()->user()->dashboard_preferences ?? [];
        $preferences['sidebar_collapsed'] = $this->sidebarCollapsed;
        auth()->user()->update(['dashboard_preferences' => $preferences]);
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
};
?>

<div x-data="{
    sidebarOpen: false,
    collapsed: @entangle('sidebarCollapsed').defer,
    init() {
        this.$watch('collapsed', (value) => {
            localStorage.setItem('sidebar_collapsed', value);
        });
    },
    toggleCollapse() {
        this.collapsed = !this.collapsed;
    }
}" x-init="init()">
    {{-- Desktop Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-[248px] flex-col border-r border-white/5 bg-brand-950 lg:flex">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
            <x-application-logo class="h-8 w-auto shrink-0 fill-current text-emerald-300" />
            <span class="leading-tight">
                <span class="block text-[15px] font-bold tracking-tight text-white">ERGE</span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.24em] text-emerald-300/80">MyFinance</span>
            </span>
        </a>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <x-sidebar-menu />
        </div>

        <div class="shrink-0 border-t border-white/5 p-3">
            <button wire:click="logout" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                <x-icon name="logout" class="h-5 w-5 shrink-0" />
                Logout
            </button>
        </div>
    </aside>

    {{-- Mobile Drawer --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div class="fixed inset-0 bg-slate-900/50" @click="sidebarOpen = false"></div>
        <aside class="fixed inset-y-0 left-0 flex w-[280px] flex-col bg-brand-950 shadow-2xl">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-white/5 px-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                    <x-application-logo class="h-7 w-auto fill-current text-emerald-300" />
                    <span class="text-sm font-bold text-white">ERGE <span class="font-medium text-emerald-300/80">MyFinance</span></span>
                </a>
                <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-300 hover:bg-white/5 hover:text-white">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-4">
                <x-sidebar-menu />
            </div>

            <div class="shrink-0 border-t border-white/5 p-3">
                <button wire:click="logout" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" />
                    Logout
                </button>
            </div>
        </aside>
    </div>

    {{-- Top Bar --}}
    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/90 shadow-topbar backdrop-blur-xl lg:pl-[248px]">
        <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
                <div class="hidden min-w-0 md:block">
                    <x-breadcrumb />
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                <span class="hidden text-sm font-semibold text-slate-700 xl:block">{{ auth()->user()->name }}</span>

                {{-- Notifications --}}
                <div x-data="{ notifOpen: false }" @click.outside="notifOpen = false" class="relative">
                    <button type="button" @click="notifOpen = ! notifOpen" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" title="Notifications" aria-label="Notifications">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if (count($this->unreadNotifications) > 0)
                            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ count($this->unreadNotifications) }}</span>
                        @endif
                    </button>

                    <div x-show="notifOpen" x-cloak class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-card-hover">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-800">Notifications</p>
                            @if (count($this->unreadNotifications) > 0)
                                <button wire:click="markAllNotificationsAsRead" class="text-xs font-medium text-brand-600 hover:text-brand-700">Mark all as read</button>
                            @endif
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse ($this->unreadNotifications as $notification)
                                <a href="{{ $notification->data['url'] ?? '#' }}" wire:click.prevent="markNotificationAsRead('{{ $notification->id }}')" class="block border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50">
                                    <p class="text-sm font-medium text-slate-800">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="mt-1 text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-slate-500">No unread notifications.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Profile --}}
                <div x-data="{ profileOpen: false }" @click.outside="profileOpen = false" class="relative">
                    <button type="button" @click="profileOpen = ! profileOpen" class="flex items-center gap-2 rounded-full p-1 transition hover:bg-slate-100" aria-label="Profile menu">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white shadow-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" x-bind:class="profileOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="profileOpen" x-cloak class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200/80 bg-white py-1 shadow-card-hover">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                        <button wire:click="logout" class="block w-full px-4 py-2 text-start text-sm text-red-600 hover:bg-slate-50">Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>