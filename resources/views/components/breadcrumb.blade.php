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
