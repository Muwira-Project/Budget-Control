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
        'realisasi.index' => ['Dashboard', 'Actual'],
                'imports.akuns' => ['Dashboard', 'Import', 'Import Account'],
                'cashflows.index' => ['Dashboard', 'Cash', 'Cash Activity'],
                'cashflows.create' => ['Dashboard', 'Cash', 'Cash Activity', 'Add Income'],
                'cash-accounts.index' => ['Dashboard', 'Cash', 'Cash Account'],
                'cash-accounts.create' => ['Dashboard', 'Cash', 'Cash Account', 'Add Cash Account'],
                'cash-accounts.edit' => ['Dashboard', 'Cash', 'Cash Account', 'Edit Cash Account'],
                'fund-transfers.index' => ['Dashboard', 'Cash', 'Fund Transfer'],
                'fund-transfers.create' => ['Dashboard', 'Cash', 'Fund Transfer', 'Add Fund Transfer'],
                'vouchers.index' => ['Dashboard', 'Cash', 'Voucher'],
                'payment-requests.index' => ['Dashboard', 'Cash', 'Payment Request'],
                'payment-requests.create' => ['Dashboard', 'Cash', 'Payment Request', 'Add Payment Request'],
                'payment-requests.edit' => ['Dashboard', 'Cash', 'Payment Request', 'Edit Payment Request'],
                'payments.index' => ['Dashboard', 'Cash', 'Settlement History'],
                'receivables.index' => ['Dashboard', 'Cash', 'Account Receivable'],
                'receivables.create' => ['Dashboard', 'Cash', 'Account Receivable', 'Add Receivable'],
                'receivables.edit' => ['Dashboard', 'Cash', 'Account Receivable', 'Edit Receivable'],
                'receivables.pay' => ['Dashboard', 'Cash', 'Account Receivable', 'Record Payment'],
                'payables.index' => ['Dashboard', 'Cash', 'Account Payable'],
                'payables.create' => ['Dashboard', 'Cash', 'Account Payable', 'Add Payable'],
                'payables.edit' => ['Dashboard', 'Cash', 'Account Payable', 'Edit Payable'],
                'payables.pay' => ['Dashboard', 'Cash', 'Account Payable', 'Record Payment'],
                'non-project-expenses.index' => ['Dashboard', 'Cash', 'Non-Project Expense'],
                'non-project-expenses.create' => ['Dashboard', 'Cash', 'Non-Project Expense', 'Add Expense'],
                'non-project-expenses.edit' => ['Dashboard', 'Cash', 'Non-Project Expense', 'Edit Expense'],
                'master-types.index' => ['Dashboard', 'Master', 'Dynamic Master'],
                'master-types.create' => ['Dashboard', 'Master', 'Dynamic Master', 'Add Master Type'],
                'master-types.edit' => ['Dashboard', 'Master', 'Dynamic Master', 'Edit Master Type'],
                'master-items.index' => ['Dashboard', 'Master', 'Dynamic Master', 'Master Items'],
                'master-items.create' => ['Dashboard', 'Master', 'Dynamic Master', 'Master Items', 'Add Item'],
                'master-items.edit' => ['Dashboard', 'Master', 'Dynamic Master', 'Master Items', 'Edit Item'],
            'exports.page' => ['Dashboard', 'Export', match ($type) {
            'akuns' => 'Export Account',
            'realisasi' => 'Export Actual',
            'vs' => 'Export Account Detail',
            default => 'Export',
        }],
        'vendors.index' => ['Dashboard', 'Master', 'Vendor'],
        'vendors.create' => ['Dashboard', 'Master', 'Vendor', 'Add Vendor'],
        'vendors.edit' => ['Dashboard', 'Master', 'Vendor', 'Edit Vendor'],
        'kategoris.index' => ['Dashboard', 'Master', 'Category'],
        'kategoris.create' => ['Dashboard', 'Master', 'Category', 'Add Category'],
        'kategoris.edit' => ['Dashboard', 'Master', 'Category', 'Edit Category'],
        'users.index' => ['Dashboard', 'Master', 'User'],
        'users.create' => ['Dashboard', 'Master', 'User', 'Add User'],
        'users.edit' => ['Dashboard', 'Master', 'User', 'Edit User'],
        'non-project-expenses.index' => ['Dashboard', 'Non-Project Expense'],
        'non-project-expenses.create' => ['Dashboard', 'Non-Project Expense', 'Add Expense'],
        'non-project-expenses.edit' => ['Dashboard', 'Non-Project Expense', 'Edit Expense'],
        'audit-log.index' => ['Dashboard', 'Audit Log'],
        'approvals.index' => ['Dashboard', 'Approval Center'],
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
