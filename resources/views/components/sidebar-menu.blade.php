<nav class="flex-1 space-y-1">
    <x-sidebar-link href="{{ route('dashboard') }}" label="Dashboard" icon="home" :active="request()->routeIs('dashboard')" />
    @if (auth()->user()->isAdmin())
        <x-sidebar-link href="{{ route('approvals.index') }}" label="Approval Center" icon="check-circle" :active="request()->routeIs('approvals.index')" />
    @endif
    @if (auth()->user()->isAdmin())
        <x-sidebar-link href="{{ route('projects.index') }}" label="Project" icon="folder" :active="request()->routeIs('projects.*')" />
        <x-sidebar-link href="{{ route('akuns.index') }}" label="Account" icon="banknotes" :active="request()->routeIs('akuns.*')" />
        <x-sidebar-link href="{{ route('budget-plans.index') }}" label="Budget" icon="clipboard" :active="request()->routeIs('budget-plans.*')" />
    @endif
    <x-sidebar-link href="{{ route('allokasis.index') }}" label="Budget Allocation" icon="document" :active="request()->routeIs('allokasis.*')" />
    <x-sidebar-link href="{{ route('monitoring.index') }}" label="Monitoring" icon="scale" :active="request()->routeIs('monitoring.*')" />

    @if (auth()->user()->isAdmin())
        <x-sidebar-dropdown label="Reports" icon="chart-pie" :active="request()->routeIs('reports.*')">
            <x-sidebar-link href="{{ route('reports.profit-loss') }}" label="Profit & Loss" :active="request()->routeIs('reports.profit-loss')" />
            <x-sidebar-link href="{{ route('reports.cash-flow') }}" label="Cash Flow per Account" :active="request()->routeIs('reports.cash-flow')" />
            <x-sidebar-link href="{{ route('reports.aging') }}" label="Aging AR/AP" :active="request()->routeIs('reports.aging')" />
        </x-sidebar-dropdown>
    @endif

    @if (config('app.show_finance_modules') && auth()->user()->isAdmin())
        <x-sidebar-link href="{{ route('cashflows.index') }}" label="Cash Activity" icon="wallet" :active="request()->routeIs('cashflows.*') || request()->routeIs('fund-transfers.*') || request()->routeIs('cash-accounts.*') || request()->routeIs('vouchers.*')" />
        <x-sidebar-link href="{{ route('ar-ap.index') }}" label="AR & AP" icon="arrow-trending-right" :active="request()->routeIs('ar-ap.*') || request()->routeIs('receivables.*') || request()->routeIs('payables.*') || request()->routeIs('payments.*')" />
    @endif

    @if (auth()->user()->isAdmin())
        <x-sidebar-link href="{{ route('realisasi.index') }}" label="Actual" icon="trending-up" :active="request()->routeIs('realisasi.*')" />

        <x-sidebar-link href="{{ route('backup.index') }}" label="Backup" icon="arrow-path" :active="request()->routeIs('backup.index')" />
        <x-sidebar-link href="{{ route('audit-log.index') }}" label="Audit Log" icon="history" :active="request()->routeIs('audit-log.index')" />

        <x-sidebar-dropdown label="Master" icon="settings" :active="request()->routeIs('kategoris.*') || request()->routeIs('vendors.*') || request()->routeIs('suppliers.*') || request()->routeIs('mandors.*') || request()->routeIs('investors.*') || request()->routeIs('users.*') || request()->routeIs('master-types.*') || request()->routeIs('master-items.*')">
            <x-sidebar-link href="{{ route('kategoris.index') }}" label="Category" :active="request()->routeIs('kategoris.*')" />
            <x-sidebar-link href="{{ route('vendors.index') }}" label="Vendor" :active="request()->routeIs('vendors.*')" />
            <x-sidebar-link href="{{ route('suppliers.index') }}" label="Supplier" :active="request()->routeIs('suppliers.*')" />
            <x-sidebar-link href="{{ route('mandors.index') }}" label="Mandor" :active="request()->routeIs('mandors.*')" />
            <x-sidebar-link href="{{ route('investors.index') }}" label="Investor" :active="request()->routeIs('investors.*')" />
            <x-sidebar-link href="{{ route('users.index') }}" label="User" :active="request()->routeIs('users.*')" />

            @foreach ($masterMenus ?? [] as $masterMenu)
                <x-sidebar-link href="{{ route('master-items.index', $masterMenu) }}" :label="$masterMenu->nama" :active="request()->routeIs('master-items.*') && (string) request()->route('masterType') === (string) $masterMenu->id" />
            @endforeach

            <div class="pt-2 mt-1 border-t border-white/10">
                <x-sidebar-link href="{{ route('master-types.create') }}" label="+ Add Master" icon="plus" :active="request()->routeIs('master-types.create')" />
                <x-sidebar-link href="{{ route('master-types.index') }}" label="Manage Master" icon="settings" :active="request()->routeIs('master-types.index')" />
            </div>
        </x-sidebar-dropdown>
    @endif

    <x-sidebar-link href="{{ route('profile') }}" label="Profile" icon="user" :active="request()->routeIs('profile')" />
</nav>