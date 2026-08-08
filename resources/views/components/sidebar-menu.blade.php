<nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
    <x-sidebar-link href="{{ route('dashboard') }}" label="Dashboard" icon="home" :active="request()->routeIs('dashboard')" />
    @if (auth()->user()->isAdmin())
    <x-sidebar-link href="{{ route('projects.index') }}" label="Project" icon="folder" :active="request()->routeIs('projects.*')" />
    <x-sidebar-link href="{{ route('akuns.index') }}" label="Account" icon="banknotes" :active="request()->routeIs('akuns.*')" />
    <x-sidebar-link href="{{ route('budget-plans.index') }}" label="Budget" icon="clipboard" :active="request()->routeIs('budget-plans.*')" />
    @endif
    <x-sidebar-link href="{{ route('monitoring.index') }}" label="Monitoring" icon="scale" :active="request()->routeIs('monitoring.*')" />
    @if (config('app.show_finance_modules') && auth()->user()->isAdmin())
    <x-sidebar-link href="{{ route('payment-requests.index') }}" label="Payment Request" icon="credit-card" :active="request()->routeIs('payment-requests.*')" />
    <x-sidebar-link href="{{ route('cashflows.index') }}" label="Cashflow" icon="wallet" :active="request()->routeIs('cashflows.*')" />

    <x-sidebar-dropdown label="Receivable & Payable" icon="receipt" :active="request()->routeIs('receivables.*') || request()->routeIs('payables.*') || request()->routeIs('payments.*')">
        <x-sidebar-link href="{{ route('receivables.index') }}" label="Account Receivable" :active="request()->routeIs('receivables.*')" />
        <x-sidebar-link href="{{ route('payables.index') }}" label="Account Payable" :active="request()->routeIs('payables.*')" />
        <x-sidebar-link href="{{ route('payments.index') }}" label="Payment" :active="request()->routeIs('payments.*')" />
    </x-sidebar-dropdown>
    @endif
    @if (auth()->user()->isAdmin())
    <x-sidebar-link href="{{ route('realisasi.index') }}" label="Actual" icon="trending-up" :active="request()->routeIs('realisasi.*')" />

    <x-sidebar-link href="{{ route('exports.page', 'vs') }}" label="Account Detail" icon="download" :active="request()->routeIs('exports.page') && request()->route('type') === 'vs'" />

    <x-sidebar-link href="{{ route('audit-log.index') }}" label="Audit Log" icon="history" :active="request()->routeIs('audit-log.index')" />

    <x-sidebar-dropdown label="Master" icon="settings" :active="request()->routeIs('vendors.*') || request()->routeIs('suppliers.*') || request()->routeIs('mandors.*') || request()->routeIs('investors.*') || request()->routeIs('kategoris.*') || request()->routeIs('users.*')">
        <x-sidebar-link href="{{ route('vendors.index') }}" label="Vendor" :active="request()->routeIs('vendors.*')" />
        <x-sidebar-link href="{{ route('suppliers.index') }}" label="Supplier" :active="request()->routeIs('suppliers.*')" />
        <x-sidebar-link href="{{ route('mandors.index') }}" label="Mandor" :active="request()->routeIs('mandors.*')" />
        <x-sidebar-link href="{{ route('investors.index') }}" label="Investor" :active="request()->routeIs('investors.*')" />
        <x-sidebar-link href="{{ route('kategoris.index') }}" label="Category" :active="request()->routeIs('kategoris.*')" />
        <x-sidebar-link href="{{ route('users.index') }}" label="User" icon="users" :active="request()->routeIs('users.*')" />
    </x-sidebar-dropdown>
    @endif

    <x-sidebar-link href="{{ route('profile') }}" label="Profile" icon="user" :active="request()->routeIs('profile')" />
</nav>

