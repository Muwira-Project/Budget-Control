{{-- ============ QUICK ACTIONS ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="bolt" class="h-5 w-5 text-brand-600" /> Quick Actions</h3>
            <p class="mt-0.5 text-sm text-slate-500">Common administrative tasks</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('company-settings.index') }}" wire:navigate class="rounded-lg bg-brand-50 p-4 ring-1 ring-brand-100 transition hover:bg-brand-100/70 text-center">
            <x-icon name="cog-6-tooth" class="mx-auto h-8 w-8 text-brand-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Company Settings</p>
            <p class="mt-1 text-xs text-slate-500">Branding, login illustration</p>
        </a>
        <a href="{{ route('backup.index') }}" wire:navigate class="rounded-lg bg-blue-50 p-4 ring-1 ring-blue-100 transition hover:bg-blue-100/70 text-center">
            <x-icon name="arrow-path" class="mx-auto h-8 w-8 text-blue-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Backup</p>
            <p class="mt-1 text-xs text-slate-500">Database backup & restore</p>
        </a>
        <a href="{{ route('audit-log.index') }}" wire:navigate class="rounded-lg bg-emerald-50 p-4 ring-1 ring-emerald-100 transition hover:bg-emerald-100/70 text-center">
            <x-icon name="history" class="mx-auto h-8 w-8 text-emerald-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Audit Log</p>
            <p class="mt-1 text-xs text-slate-500">All activity trail</p>
        </a>
        <a href="{{ route('trash.index') }}" wire:navigate class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-100 transition hover:bg-amber-100/70 text-center">
            <x-icon name="trash" class="mx-auto h-8 w-8 text-amber-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Trash</p>
            <p class="mt-1 text-xs text-slate-500">Soft deleted records</p>
        </a>
    </div>
</div>

{{-- ============ MASTER DATA MANAGEMENT ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="settings" class="h-5 w-5 text-brand-600" /> Master Data</h3>
            <p class="mt-0.5 text-sm text-slate-500">Manage categories, users, and dynamic master items</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('kategoris.index') }}" wire:navigate class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-100 transition hover:bg-slate-100 text-center">
            <x-icon name="tag" class="mx-auto h-8 w-8 text-slate-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Categories</p>
            <p class="mt-1 text-xs text-slate-500">Budgeting categories</p>
        </a>
        <a href="{{ route('users.index') }}" wire:navigate class="rounded-lg bg-indigo-50 p-4 ring-1 ring-indigo-100 transition hover:bg-indigo-100/70 text-center">
            <x-icon name="users" class="mx-auto h-8 w-8 text-indigo-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Users</p>
            <p class="mt-1 text-xs text-slate-500">Staff & admin accounts</p>
        </a>
        <a href="{{ route('master-types.index') }}" wire:navigate class="rounded-lg bg-purple-50 p-4 ring-1 ring-purple-100 transition hover:bg-purple-100/70 text-center">
            <x-icon name="settings" class="mx-auto h-8 w-8 text-purple-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Manage Master</p>
            <p class="mt-1 text-xs text-slate-500">Master types & fields</p>
        </a>
        <a href="{{ route('master-types.create') }}" wire:navigate class="rounded-lg bg-emerald-50 p-4 ring-1 ring-emerald-100 transition hover:bg-emerald-100/70 text-center">
            <x-icon name="plus" class="mx-auto h-8 w-8 text-emerald-600" />
            <p class="mt-2 text-sm font-medium text-slate-900">Add Master</p>
            <p class="mt-1 text-xs text-slate-500">Create new master type</p>
        </a>
    </div>
</div>

{{-- ============ SYSTEM INFO ============ --}}
<div class="app-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="info-circle" class="h-5 w-5 text-brand-600" /> System Information</h3>
            <p class="mt-0.5 text-sm text-slate-500">Application version and environment</p>
        </div>
    </div>
    <div class="p-5">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">App Version</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ config('app.version') ?? '1.0.0' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Environment</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ app()->environment() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">PHP Version</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ PHP_VERSION }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Laravel Version</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ app()->version() }}</dd>
            </div>
        </dl>
    </div>
</div>