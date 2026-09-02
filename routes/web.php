<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportTemplateController;
use App\Livewire\Akuns\Create as CreateAkun;
use App\Livewire\Akuns\Edit as EditAkun;
use App\Livewire\Akuns\Index as IndexAkun;
use App\Livewire\Allokasis\Create as CreateAllokasi;
use App\Livewire\Allokasis\Edit as EditAllokasi;
use App\Livewire\Approvals\Index as ApprovalsIndex;
use App\Livewire\ArAp\Index as IndexArAp;
use App\Livewire\AuditLog\Index as AuditLogIndex;
use App\Livewire\Backups\Index as IndexBackup;
use App\Livewire\Budgeting\Index as IndexBudgeting;
use App\Livewire\BudgetPlans\Create as CreateBudgetPlan;
use App\Livewire\BudgetPlans\Edit as EditBudgetPlan;
use App\Livewire\BudgetPlans\Index as IndexBudgetPlan;
use App\Livewire\CashAccounts\Create as CreateCashAccount;
use App\Livewire\CashAccounts\Edit as EditCashAccount;
use App\Livewire\CashAccounts\Index as IndexCashAccount;
use App\Livewire\Cashflows\Create as CreateCashflow;
use App\Livewire\Cashflows\Index as IndexCashflow;
use App\Livewire\CompanySettings\Index as IndexCompanySettings;
use App\Livewire\Dashboard;
use App\Livewire\Exports\Index as ExportIndex;
use App\Livewire\FundTransfers\Create as CreateFundTransfer;
use App\Livewire\FundTransfers\Index as IndexFundTransfer;
use App\Livewire\Imports\ImportAkuns;
use App\Livewire\Imports\ImportCashflows;
use App\Livewire\Imports\ImportPayables;
use App\Livewire\Imports\ImportProjects;
use App\Livewire\Imports\ImportReceivables;
use App\Livewire\Kategoris\Create as CreateKategori;
use App\Livewire\Kategoris\Edit as EditKategori;
use App\Livewire\Kategoris\Index as IndexKategori;
use App\Livewire\MasterItems\Create as CreateMasterItem;
use App\Livewire\MasterItems\Edit as EditMasterItem;
use App\Livewire\MasterItems\Index as IndexMasterItem;
use App\Livewire\MasterTypes\Create as CreateMasterType;
use App\Livewire\MasterTypes\Edit as EditMasterType;
use App\Livewire\MasterTypes\Index as IndexMasterType;
use App\Livewire\Monitoring\Create as CreateMonitoring;
use App\Livewire\Monitoring\Edit as EditMonitoring;
use App\Livewire\Monitoring\Index as IndexMonitoring;
use App\Livewire\Monitoring\Resume as ResumeMonitoring;
use App\Livewire\Payables\Create as CreatePayable;
use App\Livewire\Payables\Edit as EditPayable;
use App\Livewire\Payables\Index as IndexPayable;
use App\Livewire\Payables\Pay as PayPayable;
use App\Livewire\Payments\Index as IndexPayment;
use App\Livewire\Projects\Create as CreateProject;
use App\Livewire\Projects\Edit as EditProject;
use App\Livewire\Projects\Index as IndexProject;
use App\Livewire\Realisasi\Index as IndexRealisasi;
use App\Livewire\Realisasi\Show as ShowRealisasi;
use App\Livewire\Realisasi\Summary as SummaryRealisasi;
use App\Livewire\Receivables\Create as CreateReceivable;
use App\Livewire\Receivables\Edit as EditReceivable;
use App\Livewire\Receivables\Index as IndexReceivable;
use App\Livewire\Receivables\Pay as PayReceivable;
use App\Livewire\Reports\CashFlow as CashFlowReport;
use App\Livewire\Reports\ProfitLoss as ProfitLossReport;
use App\Livewire\Trash\Index as IndexTrash;
use App\Livewire\Users\Create as CreateUser;
use App\Livewire\Users\Edit as EditUser;
use App\Livewire\Users\Index as IndexUser;
use App\Livewire\Vouchers\Index as IndexVoucher;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))
    ->middleware('auth')
    ->name('home');

Route::middleware(['auth', 'verified', 'draft-staff'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/projects', IndexProject::class)->name('projects.index');
    Route::get('/projects/create', CreateProject::class)->name('projects.create');
    Route::get('/projects/{project}/edit', EditProject::class)->name('projects.edit');

    Route::get('/akuns', IndexAkun::class)->name('akuns.index');
    Route::get('/akuns/create', CreateAkun::class)->name('akuns.create');
    Route::get('/akuns/{akun}/edit', EditAkun::class)->name('akuns.edit');

    Route::get('/budgeting', IndexBudgeting::class)->name('budgeting.index');

    Route::get('/budget-plans', IndexBudgetPlan::class)->name('budget-plans.index');
    Route::get('/budget-plans/create', CreateBudgetPlan::class)->name('budget-plans.create');
    Route::get('/budget-plans/{budgetPlan}/edit', EditBudgetPlan::class)->name('budget-plans.edit');

    Route::get('/allokasis/create', CreateAllokasi::class)->name('allokasis.create');
    Route::get('/allokasis/{projectAkun}/edit', EditAllokasi::class)->name('allokasis.edit');

    Route::get('/monitoring', IndexMonitoring::class)->name('monitoring.index');
    Route::get('/monitoring/create', CreateMonitoring::class)->name('monitoring.create');
    Route::get('/monitoring/{monitoringPeriod}', ResumeMonitoring::class)->name('monitoring.show');
    Route::get('/monitoring/{monitoringPeriod}/edit', EditMonitoring::class)->name('monitoring.edit');
    Route::get('/ar-ap', IndexArAp::class)->name('ar-ap.index');

    Route::get('/cashflows', IndexCashflow::class)->name('cashflows.index');
    Route::get('/cashflows/create', CreateCashflow::class)->name('cashflows.create');
    Route::get('/cashflows/{cashflow}/print', function (Cashflow $cashflow) {
        return view('cashflows.print', ['cashflow' => $cashflow->load(['cashAccount', 'voucher', 'createdBy', 'akun', 'payment.payable.pihakItem', 'payment.receivable.pihakItem'])]);
    })->name('cashflows.print');

    Route::get('/cash-accounts', IndexCashAccount::class)->name('cash-accounts.index');
    Route::get('/cash-accounts/create', CreateCashAccount::class)->name('cash-accounts.create');
    Route::get('/cash-accounts/{cashAccount}/edit', EditCashAccount::class)->name('cash-accounts.edit');

    Route::get('/fund-transfers', IndexFundTransfer::class)->name('fund-transfers.index');
    Route::get('/fund-transfers/create', CreateFundTransfer::class)->name('fund-transfers.create');
    Route::get('/fund-transfers/{fundTransfer}/print', function (FundTransfer $fundTransfer) {
        return view('fund-transfers.print', ['transfer' => $fundTransfer->load(['dariCashAccount', 'keCashAccount', 'voucher', 'createdBy'])]);
    })->name('fund-transfers.print');

    Route::get('/vouchers', IndexVoucher::class)->name('vouchers.index');

    Route::get('/receivables', IndexReceivable::class)->name('receivables.index');
    Route::get('/receivables/create', CreateReceivable::class)->name('receivables.create');
    Route::get('/receivables/{receivable}/edit', EditReceivable::class)->name('receivables.edit');
    Route::get('/receivables/{receivable}/pay', PayReceivable::class)->name('receivables.pay');

    Route::get('/payables', IndexPayable::class)->name('payables.index');
    Route::get('/payables/create', CreatePayable::class)->name('payables.create');
    Route::get('/payables/{payable}/edit', EditPayable::class)->name('payables.edit');
    Route::get('/payables/{payable}/pay', PayPayable::class)->name('payables.pay');

    Route::get('/payments', IndexPayment::class)->name('payments.index');

    Route::get('/realisasi', function () {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        return $user->role?->value === 'admin'
            ? redirect()->route('realisasi.detail')
            : redirect()->route('realisasi.summary');
    })->name('realisasi.index');
    Route::get('/realisasi/detail', IndexRealisasi::class)->name('realisasi.detail');
    Route::get('/realisasi/summary', SummaryRealisasi::class)->name('realisasi.summary');

    Route::get('/reports/profit-loss', ProfitLossReport::class)->name('reports.profit-loss');
    Route::get('/reports/cash-flow', CashFlowReport::class)->name('reports.cash-flow');
    Route::get('/realisasi/{realisasi}', ShowRealisasi::class)->name('realisasi.show');

    Route::get('/kategoris', IndexKategori::class)->name('kategoris.index');
    Route::get('/kategoris/create', CreateKategori::class)->name('kategoris.create');
    Route::get('/kategoris/{kategori}/edit', EditKategori::class)->name('kategoris.edit');

    Route::get('/master-types', IndexMasterType::class)->name('master-types.index');
    Route::get('/master-types/create', CreateMasterType::class)->name('master-types.create');
    Route::get('/master-types/{masterType}/edit', EditMasterType::class)->name('master-types.edit');
    Route::get('/master-types/{masterType}/items', IndexMasterItem::class)->name('master-items.index');
    Route::get('/master-types/{masterType}/items/create', CreateMasterItem::class)->name('master-items.create');
    Route::get('/master-items/{masterItem}/edit', EditMasterItem::class)->name('master-items.edit');

    Route::get('/users', IndexUser::class)->name('users.index');
    Route::get('/users/create', CreateUser::class)->name('users.create');
    Route::get('/users/{user}/edit', EditUser::class)->name('users.edit');

    Route::get('/audit-log', AuditLogIndex::class)->name('audit-log.index');

    Route::get('/trash', IndexTrash::class)->name('trash.index');

    Route::get('/company-settings', IndexCompanySettings::class)->name('company-settings.index');

    Route::get('/approvals', ApprovalsIndex::class)->name('approvals.index');

    Route::get('/backup', IndexBackup::class)->name('backup.index');

    Route::get('/import/akuns', ImportAkuns::class)->name('imports.akuns');
    Route::get('/import/akuns/template', [ImportTemplateController::class, 'akun'])->name('imports.akuns.template');
    Route::get('/import/projects/template', [ImportTemplateController::class, 'project'])->name('imports.projects.template');
    Route::get('/import/cashflows', ImportCashflows::class)->name('imports.cashflows');
    Route::get('/import/cashflows/template', [ImportTemplateController::class, 'cashflow'])->name('imports.cashflows.template');
    Route::get('/import/projects', ImportProjects::class)->name('imports.projects');
    Route::get('/import/projects/export', [ImportTemplateController::class, 'exportProjects'])->name('imports.projects.export');
    Route::get('/import/receivables', ImportReceivables::class)->name('imports.receivables');
    Route::get('/import/receivables/template', [ImportTemplateController::class, 'receivable'])->name('imports.receivables.template');
    Route::get('/import/payables', ImportPayables::class)->name('imports.payables');
    Route::get('/import/payables/template', [ImportTemplateController::class, 'payable'])->name('imports.payables.template');

    Route::get('/export/{type}', ExportIndex::class)
        ->whereIn('type', ['akuns', 'realisasi', 'vs', 'receivables', 'payables'])
        ->name('exports.page');
    Route::get('/export/akuns/file', [ExportController::class, 'akuns'])->name('exports.akuns');
    Route::get('/export/realisasi/file', [ExportController::class, 'realisasi'])->name('exports.realisasi');
    Route::get('/export/akun-vs-realisasi/file', [ExportController::class, 'akunVsRealisasi'])->name('exports.vs');
    Route::get('/export/monitoring-summary/file', [ExportController::class, 'monitoringSummary'])->name('exports.monitoring-summary');
    Route::get('/export/monitoring-variance/file/{period}', [ExportController::class, 'monitoringVariance'])->name('exports.monitoring-variance');
    Route::get('/export/monitoring-variance-detail/file/{period}', [ExportController::class, 'monitoringVarianceDetail'])->name('exports.monitoring-variance-detail');
    Route::get('/export/receivables/file', [ExportController::class, 'receivables'])->name('exports.receivables');
    Route::get('/export/payables/file', [ExportController::class, 'payables'])->name('exports.payables');
    Route::get('/export/cashflows/file', [ExportController::class, 'cashflows'])->name('exports.cashflows');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
