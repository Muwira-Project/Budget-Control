@php
$name = request()->route()?->getName() ?? '';
$type = request()->route()?->parameter('type');

$breadcrumbs = [
['label' => 'Dashboard', 'route' => 'dashboard', 'condition' => true],
];

// Build the breadcrumb trail based on current route
match ($name) {
'projects.index' => $breadcrumbs[] = ['label' => 'Project', 'route' => 'projects.index'],
'projects.create' => [$breadcrumbs[] = ['label' => 'Project', 'route' => 'projects.index'], $breadcrumbs[] = ['label' => 'Add Project']],
'projects.edit' => [$breadcrumbs[] = ['label' => 'Project', 'route' => 'projects.index'], $breadcrumbs[] = ['label' => 'Edit Project']],
'akuns.index' => $breadcrumbs[] = ['label' => 'Account', 'route' => 'akuns.index'],
'akuns.create' => [$breadcrumbs[] = ['label' => 'Account', 'route' => 'akuns.index'], $breadcrumbs[] = ['label' => 'Add Account']],
'akuns.edit' => [$breadcrumbs[] = ['label' => 'Account', 'route' => 'akuns.index'], $breadcrumbs[] = ['label' => 'Edit Account']],
'budgeting.index' => $breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'],
'budget-plans.index' => [$breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'], $breadcrumbs[] = ['label' => 'Budget Plan', 'route' => 'budget-plans.index']],
'budget-plans.create' => [$breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'], $breadcrumbs[] = ['label' => 'Budget Plan', 'route' => 'budget-plans.index'], $breadcrumbs[] = ['label' => 'Add Budget']],
'budget-plans.edit' => [$breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'], $breadcrumbs[] = ['label' => 'Budget Plan', 'route' => 'budget-plans.index'], $breadcrumbs[] = ['label' => 'Edit Budget']],
'allokasis.create' => [$breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'], $breadcrumbs[] = ['label' => 'Add Non-Project']],
'allokasis.edit' => [$breadcrumbs[] = ['label' => 'Budgeting', 'route' => 'budgeting.index'], $breadcrumbs[] = ['label' => 'Edit Allocation']],
'realisasi.index' => $breadcrumbs[] = ['label' => 'Actual', 'route' => 'realisasi.index'],
'imports.akuns' => [$breadcrumbs[] = ['label' => 'Import', 'route' => null], $breadcrumbs[] = ['label' => 'Import Account']],
'cashflows.index' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index']],
'cashflows.create' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Add Cash Entry']],
'cash-accounts.index' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Cash Account', 'route' => 'cash-accounts.index']],
'cash-accounts.create' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Cash Account', 'route' => 'cash-accounts.index'], $breadcrumbs[] = ['label' => 'Add Cash Account']],
'cash-accounts.edit' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Cash Account', 'route' => 'cash-accounts.index'], $breadcrumbs[] = ['label' => 'Edit Cash Account']],
'fund-transfers.index' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Fund Transfer', 'route' => 'fund-transfers.index']],
'fund-transfers.create' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Fund Transfer', 'route' => 'fund-transfers.index'], $breadcrumbs[] = ['label' => 'Add Fund Transfer']],
'vouchers.index' => [$breadcrumbs[] = ['label' => 'Cash Activity', 'route' => 'cashflows.index'], $breadcrumbs[] = ['label' => 'Voucher', 'route' => 'vouchers.index']],
'ar-ap.index' => $breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'],
'payments.index' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Settlement History', 'route' => 'payments.index']],
'receivables.index' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Receivable', 'route' => 'receivables.index']],
'receivables.create' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Receivable', 'route' => 'receivables.index'], $breadcrumbs[] = ['label' => 'Add Receivable']],
'receivables.edit' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Receivable', 'route' => 'receivables.index'], $breadcrumbs[] = ['label' => 'Edit Receivable']],
'receivables.pay' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Receivable', 'route' => 'receivables.index'], $breadcrumbs[] = ['label' => 'Record Payment']],
'payables.index' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Payable', 'route' => 'payables.index']],
'payables.create' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Payable', 'route' => 'payables.index'], $breadcrumbs[] = ['label' => 'Add Payable']],
'payables.edit' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Payable', 'route' => 'payables.index'], $breadcrumbs[] = ['label' => 'Edit Payable']],
'payables.pay' => [$breadcrumbs[] = ['label' => 'AR & AP', 'route' => 'ar-ap.index'], $breadcrumbs[] = ['label' => 'Account Payable', 'route' => 'payables.index'], $breadcrumbs[] = ['label' => 'Record Payment']],
'master-types.index' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Manage Master']],
'master-types.create' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Add Master']],
'master-types.edit' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Edit Master']],
'master-items.index' => $breadcrumbs[] = ['label' => 'Master', 'route' => null],
'master-items.create' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Add Item']],
'master-items.edit' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Edit Item']],
'exports.page' => [$breadcrumbs[] = ['label' => 'Export', 'route' => null], $breadcrumbs[] = ['label' => match ($type) {
'akuns' => 'Export Account',
'realisasi' => 'Export Actual',
'vs' => 'Export Account Detail',
default => 'Export',
}]],
'kategoris.index' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Category', 'route' => 'kategoris.index']],
'kategoris.create' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Category', 'route' => 'kategoris.index'], $breadcrumbs[] = ['label' => 'Add Category']],
'kategoris.edit' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'Category', 'route' => 'kategoris.index'], $breadcrumbs[] = ['label' => 'Edit Category']],
'users.index' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'User', 'route' => 'users.index']],
'users.create' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'User', 'route' => 'users.index'], $breadcrumbs[] = ['label' => 'Add User']],
'users.edit' => [$breadcrumbs[] = ['label' => 'Master', 'route' => null], $breadcrumbs[] = ['label' => 'User', 'route' => 'users.index'], $breadcrumbs[] = ['label' => 'Edit User']],
'audit-log.index' => $breadcrumbs[] = ['label' => 'Audit Log', 'route' => 'audit-log.index'],
'reports.profit-loss' => [$breadcrumbs[] = ['label' => 'Reports', 'route' => null], $breadcrumbs[] = ['label' => 'Profit & Loss']],
'reports.cash-flow' => [$breadcrumbs[] = ['label' => 'Reports', 'route' => null], $breadcrumbs[] = ['label' => 'Cash Flow per Account']],
'profile' => $breadcrumbs[] = ['label' => 'Profile', 'route' => 'profile'],
default => [],
};
@endphp

<nav aria-label="Breadcrumb">
    <ol class="flex min-w-0 flex-wrap items-center gap-1.5 text-[13px] text-slate-500">
        @foreach ($breadcrumbs as $index => $item)
        @if ($index > 0)
        <li aria-hidden="true">
            <x-icon name="chevron-right" class="h-3.5 w-3.5 text-gray-400" />
        </li>
        @endif
        <li>
            @if ($index === count($breadcrumbs) - 1)
            {{-- Last item: Current page (not clickable) --}}
            <span class="block max-w-[220px] truncate font-semibold text-slate-800">{{ $item['label'] }}</span>
            @elseif (isset($item['route']) && $item['route'])
            {{-- Middle/First items with route: Clickable links --}}
            <a href="{{ route($item['route']) }}" wire:navigate class="font-medium text-slate-500 transition hover:text-brand-600">{{ $item['label'] }}</a>
            @else
            {{-- Items without route: Static text --}}
            <span class="text-slate-500">{{ $item['label'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>