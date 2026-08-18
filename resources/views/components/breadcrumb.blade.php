@php
    $name = request()->route()?->getName() ?? '';
    $type = request()->route()?->parameter('type');

    $labels = match ($name) {
        'dashboard' => ['Dashboard'],
        'projects.index' => ['Dashboard', 'Project'],
        'projects.create' => ['Dashboard', 'Project', 'Add Project'],
        'projects.edit' => ['Dashboard', 'Project', 'Edit Project'],
        'akuns.index' => ['Dashboard', 'Account'],
        'akuns.create' => ['Dashboard', 'Account', 'Add Account'],
        'akuns.edit' => ['Dashboard', 'Account', 'Edit Account'],
        'budgeting.index' => ['Dashboard', 'Budgeting'],
        'budget-plans.index' => ['Dashboard', 'Budgeting', 'Budget Plan'],
        'budget-plans.create' => ['Dashboard', 'Budgeting', 'Budget Plan', 'Add Budget'],
        'budget-plans.edit' => ['Dashboard', 'Budgeting', 'Budget Plan', 'Edit Budget'],
        'allokasis.index' => ['Dashboard', 'Budgeting', 'Allocation'],
        'allokasis.create' => ['Dashboard', 'Budgeting', 'Allocation', 'Add Allocation'],
        'allokasis.edit' => ['Dashboard', 'Budgeting', 'Allocation', 'Edit Allocation'],
        'realisasi.index' => ['Dashboard', 'Actual'],
        'imports.akuns' => ['Dashboard', 'Import', 'Import Account'],
        'cashflows.index' => ['Dashboard', 'Cash Activity'],
        'cashflows.create' => ['Dashboard', 'Cash Activity', 'Add Cash Entry'],
        'cash-accounts.index' => ['Dashboard', 'Cash Activity', 'Cash Account'],
        'cash-accounts.create' => ['Dashboard', 'Cash Activity', 'Cash Account', 'Add Cash Account'],
        'cash-accounts.edit' => ['Dashboard', 'Cash Activity', 'Cash Account', 'Edit Cash Account'],
        'fund-transfers.index' => ['Dashboard', 'Cash Activity', 'Fund Transfer'],
        'fund-transfers.create' => ['Dashboard', 'Cash Activity', 'Fund Transfer', 'Add Fund Transfer'],
        'vouchers.index' => ['Dashboard', 'Cash Activity', 'Voucher'],
        'ar-ap.index' => ['Dashboard', 'AR & AP'],
        'payments.index' => ['Dashboard', 'AR & AP', 'Settlement History'],
        'receivables.index' => ['Dashboard', 'AR & AP', 'Account Receivable'],
        'receivables.create' => ['Dashboard', 'AR & AP', 'Account Receivable', 'Add Receivable'],
        'receivables.edit' => ['Dashboard', 'AR & AP', 'Account Receivable', 'Edit Receivable'],
        'receivables.pay' => ['Dashboard', 'AR & AP', 'Account Receivable', 'Record Payment'],
        'payables.index' => ['Dashboard', 'AR & AP', 'Account Payable'],
        'payables.create' => ['Dashboard', 'AR & AP', 'Account Payable', 'Add Payable'],
        'payables.edit' => ['Dashboard', 'AR & AP', 'Account Payable', 'Edit Payable'],
        'payables.pay' => ['Dashboard', 'AR & AP', 'Account Payable', 'Record Payment'],
        'master-types.index' => ['Dashboard', 'Master', 'Manage Master'],
        'master-types.create' => ['Dashboard', 'Master', 'Add Master'],
        'master-types.edit' => ['Dashboard', 'Master', 'Edit Master'],
        'master-items.index' => ['Dashboard', 'Master'],
        'master-items.create' => ['Dashboard', 'Master', 'Add Item'],
        'master-items.edit' => ['Dashboard', 'Master', 'Edit Item'],
        'exports.page' => ['Dashboard', 'Export', match ($type) {
            'akuns' => 'Export Account',
            'realisasi' => 'Export Actual',
            'vs' => 'Export Account Detail',
            default => 'Export',
        }],
        'vendors.index' => ['Dashboard', 'Master', 'Vendor'],
        'vendors.create' => ['Dashboard', 'Master', 'Vendor', 'Add Vendor'],
        'vendors.edit' => ['Dashboard', 'Master', 'Vendor', 'Edit Vendor'],
        'suppliers.index' => ['Dashboard', 'Master', 'Supplier'],
        'suppliers.create' => ['Dashboard', 'Master', 'Supplier', 'Add Supplier'],
        'suppliers.edit' => ['Dashboard', 'Master', 'Supplier', 'Edit Supplier'],
        'mandors.index' => ['Dashboard', 'Master', 'Mandor'],
        'mandors.create' => ['Dashboard', 'Master', 'Mandor', 'Add Mandor'],
        'mandors.edit' => ['Dashboard', 'Master', 'Mandor', 'Edit Mandor'],
        'investors.index' => ['Dashboard', 'Master', 'Investor'],
        'investors.create' => ['Dashboard', 'Master', 'Investor', 'Add Investor'],
        'investors.edit' => ['Dashboard', 'Master', 'Investor', 'Edit Investor'],
        'kategoris.index' => ['Dashboard', 'Master', 'Category'],
        'kategoris.create' => ['Dashboard', 'Master', 'Category', 'Add Category'],
        'kategoris.edit' => ['Dashboard', 'Master', 'Category', 'Edit Category'],
        'users.index' => ['Dashboard', 'Master', 'User'],
        'users.create' => ['Dashboard', 'Master', 'User', 'Add User'],
        'users.edit' => ['Dashboard', 'Master', 'User', 'Edit User'],
        'audit-log.index' => ['Dashboard', 'Audit Log'],
        'approvals.index' => ['Dashboard', 'Approval Center'],
        'reports.profit-loss' => ['Dashboard', 'Reports', 'Profit & Loss'],
        'reports.cash-flow' => ['Dashboard', 'Reports', 'Cash Flow per Account'],
        'profile' => ['Dashboard', 'Profile'],
        default => ['Dashboard'],
    };
@endphp

<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1.5 text-[13px] text-slate-500">
        @foreach ($labels as $index => $label)
            @if ($index > 0)
                <li aria-hidden="true">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-gray-400" />
                </li>
            @endif
            <li>
                @if ($index === 0)
                    <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-slate-500 transition hover:text-brand-600">{{ $label }}</a>
                @elseif ($index === count($labels) - 1)
                    <span class="font-semibold text-slate-800">{{ $label }}</span>
                @else
                    <span class="text-slate-500">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>