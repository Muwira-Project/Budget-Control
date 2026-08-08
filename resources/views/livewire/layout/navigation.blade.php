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
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
};
?>

<div x-data="{ sidebarOpen: false }">
    <!-- Desktop Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-emerald-400/10 bg-[#054316] lg:flex">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 border-b border-emerald-400/10 px-5 py-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 p-1"><x-application-logo class="h-full w-full fill-current text-white" /></span>
            <span class="leading-tight">
                <span class="block text-sm font-bold text-white">{{ $companyName ?? 'myfinance' }}</span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-200/70">myfinance</span>
            </span>
        </a>

        <x-sidebar-menu />

        <div class="border-t border-emerald-400/10 p-3">
            <button wire:click="logout" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-emerald-500/10 hover:text-emerald-100">
                <x-icon name="logout" class="h-5 w-5 shrink-0" />
                Logout
            </button>
        </div>
    </aside>

    <!-- Mobile Drawer -->
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div class="fixed inset-0 bg-gray-900/50" @click="sidebarOpen = false"></div>
        <aside class="fixed inset-y-0 left-0 flex w-64 flex-col bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                    <x-application-logo class="h-8 w-auto fill-current text-blue-600" />
                    <span class="text-sm font-bold text-gray-900">{{ $companyName ?? 'myfinance' }}</span>
                </a>
                <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <x-sidebar-menu />

            <div class="border-t border-gray-200 p-3">
                <button wire:click="logout" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" />
                    Logout
                </button>
            </div>
        </aside>
    </div>

    <!-- Topbar -->
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 shadow-sm shadow-slate-950/[0.02] backdrop-blur-xl">
        <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Open menu">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
                <span class="text-sm font-bold text-slate-900 lg:hidden">My Finance</span>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                <span class="hidden text-sm font-semibold text-slate-700 md:block">{{ auth()->user()->name }}</span>

                <div x-data="{ notifOpen: false }" @click.outside="notifOpen = false" class="relative">
                    <button type="button" @click="notifOpen = ! notifOpen" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100" title="Notification" aria-label="Notifications">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if (count($this->unreadNotifications) > 0)
                            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ count($this->unreadNotifications) }}</span>
                        @endif
                    </button>

                    <div x-show="notifOpen" x-cloak class="absolute right-0 mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                            <p class="text-sm font-semibold text-gray-800">Notifications</p>
                            @if (count($this->unreadNotifications) > 0)
                                <button wire:click="markAllNotificationsAsRead" class="text-xs font-medium text-blue-600 hover:text-blue-800">Mark all as read</button>
                            @endif
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse ($this->unreadNotifications as $notification)
                                <a href="{{ $notification->data['url'] ?? '#' }}" wire:click.prevent="markNotificationAsRead('{{ $notification->id }}')" class="block border-b border-gray-50 px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm font-medium text-gray-800">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="mt-1 text-[10px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-gray-500">No unread notifications.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div x-data="{ profileOpen: false }" @click.outside="profileOpen = false" class="relative">
                    <button type="button" @click="profileOpen = ! profileOpen" class="flex items-center gap-1.5 rounded-full p-1 transition hover:bg-slate-100" aria-label="Profile menu">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" x-bind:class="profileOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="profileOpen" x-cloak class="absolute right-0 mt-2 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                        <div class="border-b border-gray-100 px-4 py-2">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                        <button wire:click="logout" class="block w-full px-4 py-2 text-start text-sm text-red-600 hover:bg-gray-50">Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>

