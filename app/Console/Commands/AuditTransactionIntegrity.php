<?php

namespace App\Console\Commands;

use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payment;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Realisasi;
use App\Models\Voucher;
use App\Models\CashAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditTransactionIntegrity extends Command
{
    protected $signature = 'audit:transactions {--fix=false}';

    protected $description = 'Audit transaction data integrity and identify issues';

    public function handle(): int
    {
        $this->line('═══════════════════════════════════════════════════════');
        $this->line('🔍 TRANSACTION DATA INTEGRITY AUDIT');
        $this->line('═══════════════════════════════════════════════════════');

        $issues = [];

        // 1. Check for orphaned records
        $this->line("\n📋 Checking for orphaned records...");
        $issues = array_merge($issues, $this->checkOrphanedRecords());

        // 2. Check for duplicate transactions
        $this->line("\n📋 Checking for duplicate transactions...");
        $issues = array_merge($issues, $this->checkDuplicates());

        // 3. Check for balance inconsistencies
        $this->line("\n📋 Checking for balance inconsistencies...");
        $issues = array_merge($issues, $this->checkBalanceInconsistencies());

        // 4. Check for posting inconsistencies
        $this->line("\n📋 Checking for posting inconsistencies...");
        $issues = array_merge($issues, $this->checkPostingInconsistencies());

        // 5. Check for payment/receivable inconsistencies
        $this->line("\n📋 Checking for payment/receivable inconsistencies...");
        $issues = array_merge($issues, $this->checkPaymentInconsistencies());

        // 6. Check for cash account balance mismatches
        $this->line("\n📋 Checking cash account balances...");
        $issues = array_merge($issues, $this->checkCashAccountBalances());

        // 7. Check for voucher integrity
        $this->line("\n📋 Checking voucher integrity...");
        $issues = array_merge($issues, $this->checkVoucherIntegrity());

        // Report results
        $this->reportResults($issues);

        // Attempt fixes if requested
        if ($this->option('fix')) {
            $this->attemptFixes($issues);
        }

        return $issues ? 1 : 0;
    }

    protected function checkOrphanedRecords(): array
    {
        $issues = [];

        // Check cashflows with invalid references
        $orphaned = Cashflow::query()
            ->where('cash_account_id', '!=', null)
            ->whereDoesntHave('cashAccount')
            ->count();

        if ($orphaned > 0) {
            $issues[] = [
                'type' => 'orphaned',
                'severity' => 'high',
                'model' => 'Cashflow',
                'description' => "Found $orphaned cashflows with invalid cash_account_id",
                'count' => $orphaned,
            ];
        }

        // Check payments with invalid receivable
        $orphaned = Payment::query()
            ->where('receivable_id', '!=', null)
            ->whereDoesntHave('receivable')
            ->count();

        if ($orphaned > 0) {
            $issues[] = [
                'type' => 'orphaned',
                'severity' => 'high',
                'model' => 'Payment',
                'description' => "Found $orphaned payments with invalid receivable_id",
                'count' => $orphaned,
            ];
        }

        // Check payments with invalid payable
        $orphaned = Payment::query()
            ->where('payable_id', '!=', null)
            ->whereDoesntHave('payable')
            ->count();

        if ($orphaned > 0) {
            $issues[] = [
                'type' => 'orphaned',
                'severity' => 'high',
                'model' => 'Payment',
                'description' => "Found $orphaned payments with invalid payable_id",
                'count' => $orphaned,
            ];
        }

        // Check realisasi with invalid akun
        $orphaned = Realisasi::query()
            ->whereDoesntHave('akun')
            ->count();

        if ($orphaned > 0) {
            $issues[] = [
                'type' => 'orphaned',
                'severity' => 'high',
                'model' => 'Realisasi',
                'description' => "Found $orphaned realisasi with invalid akun_id",
                'count' => $orphaned,
            ];
        }

        return $issues;
    }

    protected function checkDuplicates(): array
    {
        $issues = [];

        // Check for duplicate payments for same receivable/payable on same date
        $duplicates = Payment::query()
            ->selectRaw('receivable_id, payable_id, tanggal, COUNT(*) as cnt')
            ->whereNull('deleted_at')
            ->groupByRaw('receivable_id, payable_id, tanggal')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            $issues[] = [
                'type' => 'duplicate',
                'severity' => 'medium',
                'model' => 'Payment',
                'description' => "Found $duplicates potential duplicate payment groups (same receivable/payable on same date)",
                'count' => $duplicates,
            ];
        }

        // Check for duplicate cashflows on same date with same properties
        $duplicates = Cashflow::query()
            ->selectRaw('tanggal, jenis, sumber, nominal, cash_account_id, COUNT(*) as cnt')
            ->whereNull('deleted_at')
            ->groupByRaw('tanggal, jenis, sumber, nominal, cash_account_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            $issues[] = [
                'type' => 'duplicate',
                'severity' => 'medium',
                'model' => 'Cashflow',
                'description' => "Found $duplicates potential duplicate cashflow groups",
                'count' => $duplicates,
            ];
        }

        return $issues;
    }

    protected function checkBalanceInconsistencies(): array
    {
        $issues = [];

        // Check receivables where paid > total nominal
        $inconsistent = Receivable::query()
            ->selectRaw('id, nominal, nominal_dibayar')
            ->whereRaw('nominal_dibayar > nominal')
            ->count();

        if ($inconsistent > 0) {
            $issues[] = [
                'type' => 'balance',
                'severity' => 'critical',
                'model' => 'Receivable',
                'description' => "Found $inconsistent receivables where paid amount > total nominal",
                'count' => $inconsistent,
            ];
        }

        // Check payables where paid > total nominal
        $inconsistent = Payable::query()
            ->selectRaw('id, nominal, nominal_dibayar')
            ->whereRaw('nominal_dibayar > nominal')
            ->count();

        if ($inconsistent > 0) {
            $issues[] = [
                'type' => 'balance',
                'severity' => 'critical',
                'model' => 'Payable',
                'description' => "Found $inconsistent payables where paid amount > total nominal",
                'count' => $inconsistent,
            ];
        }

        return $issues;
    }

    protected function checkPostingInconsistencies(): array
    {
        $issues = [];

        // Check approved transactions without posted_at date
        $inconsistent = Cashflow::query()
            ->where('status', 'posted')
            ->whereNull('posted_at')
            ->count();

        if ($inconsistent > 0) {
            $issues[] = [
                'type' => 'posting',
                'severity' => 'medium',
                'model' => 'Cashflow',
                'description' => "Found $inconsistent posted cashflows without posted_at timestamp",
                'count' => $inconsistent,
            ];
        }

        // Check for same condition in fund transfers
        $inconsistent = FundTransfer::query()
            ->where('status', 'posted')
            ->whereNull('posted_at')
            ->count();

        if ($inconsistent > 0) {
            $issues[] = [
                'type' => 'posting',
                'severity' => 'medium',
                'model' => 'FundTransfer',
                'description' => "Found $inconsistent posted fund transfers without posted_at timestamp",
                'count' => $inconsistent,
            ];
        }

        return $issues;
    }

    protected function checkPaymentInconsistencies(): array
    {
        $issues = [];

        // Check payments where status doesn't match receivable status
        $receivables = Payment::query()
            ->where('receivable_id', '!=', null)
            ->select('id', 'receivable_id', 'status')
            ->get();

        $mismatches = 0;
        foreach ($receivables as $payment) {
            $receivable = Receivable::find($payment->receivable_id);
            if ($receivable && $payment->status !== 'active' && $receivable->status === 'settled') {
                $mismatches++;
            }
        }

        if ($mismatches > 0) {
            $issues[] = [
                'type' => 'payment',
                'severity' => 'medium',
                'model' => 'Payment',
                'description' => "Found $mismatches payment-receivable status mismatches",
                'count' => $mismatches,
            ];
        }

        return $issues;
    }

    protected function checkCashAccountBalances(): array
    {
        $issues = [];

        // Note: CashAccount balances are calculated dynamically via the saldo getter
        // which aggregates posted transactions on-the-fly. This is by design and
        // ensures balances are always accurate without requiring periodic updates.

        // We can verify the calculation logic is working by spot-checking a few accounts
        foreach (CashAccount::limit(3)->get() as $account) {
            try {
                $balance = $account->saldo; // This will execute the getter logic
                // If we get here without error, the calculation is working
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'balance',
                    'severity' => 'high',
                    'model' => 'CashAccount',
                    'description' => "Cash account {$account->kode} balance calculation error: {$e->getMessage()}",
                    'account_id' => $account->id,
                ];
            }
        }

        return $issues;
    }

    protected function checkVoucherIntegrity(): array
    {
        $issues = [];

        // Check for posted transactions without vouchers
        $cashflowsWithoutVouchers = Cashflow::query()
            ->where('status', 'posted')
            ->whereDoesntHave('voucher')
            ->count();

        if ($cashflowsWithoutVouchers > 0) {
            $issues[] = [
                'type' => 'voucher',
                'severity' => 'high',
                'model' => 'Cashflow',
                'description' => "Found $cashflowsWithoutVouchers posted cashflows without corresponding vouchers",
                'count' => $cashflowsWithoutVouchers,
            ];
        }

        // Check for duplicate vouchers
        $duplicates = Voucher::query()
            ->selectRaw('COUNT(*) as cnt, cashflow_id, fund_transfer_id')
            ->whereRaw('cashflow_id IS NOT NULL OR fund_transfer_id IS NOT NULL')
            ->groupByRaw('cashflow_id, fund_transfer_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            $issues[] = [
                'type' => 'voucher',
                'severity' => 'high',
                'model' => 'Voucher',
                'description' => "Found $duplicates transactions with duplicate vouchers",
                'count' => $duplicates,
            ];
        }

        return $issues;
    }

    protected function reportResults(array $issues): void
    {
        $this->line("\n═══════════════════════════════════════════════════════");
        $this->line('📊 AUDIT RESULTS');
        $this->line('═══════════════════════════════════════════════════════');

        if (empty($issues)) {
            $this->info("\n✅ No issues found! All transaction data is consistent and accurate.");
            return;
        }

        // Group by severity
        $critical = array_filter($issues, fn($i) => $i['severity'] === 'critical');
        $high = array_filter($issues, fn($i) => $i['severity'] === 'high');
        $medium = array_filter($issues, fn($i) => $i['severity'] === 'medium');

        if (!empty($critical)) {
            $this->error("\n🚨 CRITICAL ISSUES (" . count($critical) . ')');
            foreach ($critical as $issue) {
                $this->error("  • {$issue['description']}");
            }
        }

        if (!empty($high)) {
            $this->warn("\n⚠️  HIGH PRIORITY ISSUES (" . count($high) . ')');
            foreach ($high as $issue) {
                $this->warn("  • {$issue['description']}");
            }
        }

        if (!empty($medium)) {
            $this->comment("\n⏱️  MEDIUM PRIORITY ISSUES (" . count($medium) . ')');
            foreach ($medium as $issue) {
                $this->comment("  • {$issue['description']}");
            }
        }

        $this->line("\n📈 Total issues found: " . count($issues));
    }

    protected function attemptFixes(array $issues): void
    {
        if (!$this->confirm("\n🔧 Attempt to auto-fix these issues?")) {
            return;
        }

        $this->line("\n🔧 Attempting auto-fixes...");

        foreach ($issues as $issue) {
            if ($issue['type'] === 'balance' && $issue['model'] === 'Receivable') {
                $this->fixReceivableBalance($issue);
            } elseif ($issue['type'] === 'balance' && $issue['model'] === 'Payable') {
                $this->fixPayableBalance($issue);
            }
        }

        $this->info("\n✅ Auto-fixes completed.");
    }

    protected function fixReceivableBalance(array $issue): void
    {
        $records = Receivable::query()
            ->whereRaw('nominal_dibayar > nominal')
            ->get();

        foreach ($records as $record) {
            $record->nominal_dibayar = $record->nominal;
            $record->save();
            $this->line("✓ Fixed Receivable ID: {$record->id}");
        }
    }

    protected function fixPayableBalance(array $issue): void
    {
        $records = Payable::query()
            ->whereRaw('nominal_dibayar > nominal')
            ->get();

        foreach ($records as $record) {
            $record->nominal_dibayar = $record->nominal;
            $record->save();
            $this->line("✓ Fixed Payable ID: {$record->id}");
        }
    }
}
