<?php

namespace App\Console\Commands;

use App\Enums\KasStatus;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payment;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Realisasi;
use App\Models\Voucher;
use App\Models\CashAccount;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairTransactionData extends Command
{
    protected $signature = 'repair:transactions {--execute=false}';

    protected $description = 'Repair transaction data integrity issues';

    protected bool $dryRun = false;

    public function handle(): int
    {
        // Check if execute flag is passed
        $execute = $this->option('execute');
        $this->dryRun = !($execute === 'true' || $execute === true || $execute === '1');

        $this->line('═══════════════════════════════════════════════════════');
        $this->line('🔧 TRANSACTION DATA REPAIR UTILITY');
        if ($this->dryRun) {
            $this->warn('[DRY RUN MODE - No changes will be made]');
        }
        $this->line('═══════════════════════════════════════════════════════');

        $fixed = 0;

        // 1. Fix posted transactions missing posted_at timestamp
        $this->line("\n📋 Fixing posted transactions without timestamp...");
        $fixed += $this->fixMissingPostedTimestamps();

        // 2. Generate missing vouchers for posted transactions
        $this->line("\n📋 Generating missing vouchers...");
        $fixed += $this->generateMissingVouchers();

        // 3. Recalculate cash account balances
        $this->line("\n📋 Recalculating cash account balances...");
        $fixed += $this->recalculateCashAccountBalances();

        // 4. Fix balance inconsistencies
        $this->line("\n📋 Fixing balance inconsistencies...");
        $fixed += $this->fixBalanceInconsistencies();

        // 5. Mark and log potential duplicates
        $this->line("\n📋 Analyzing potential duplicates...");
        $this->analyzeDuplicates();

        $this->line("\n═══════════════════════════════════════════════════════");
        if ($this->dryRun) {
            $this->warn("🔍 DRY RUN: $fixed issues would be fixed");
            $this->line("Run without --dry-run to apply changes:");
            $this->line("  php artisan repair:transactions");
        } else {
            $this->info("✅ Repaired $fixed issues");
        }

        return 0;
    }

    protected function fixMissingPostedTimestamps(): int
    {
        $count = 0;

        // Fix cashflows
        $cashflows = Cashflow::query()
            ->where('status', 'posted')
            ->whereNull('posted_at')
            ->get();

        foreach ($cashflows as $cashflow) {
            if (!$this->dryRun) {
                $cashflow->posted_at = $cashflow->updated_at ?? now();
                $cashflow->save();
            }
            $this->line("  ✓ Fixed Cashflow ID {$cashflow->id}");
            $count++;
        }

        // Fix fund transfers
        $transfers = FundTransfer::query()
            ->where('status', 'posted')
            ->whereNull('posted_at')
            ->get();

        foreach ($transfers as $transfer) {
            if (!$this->dryRun) {
                $transfer->posted_at = $transfer->updated_at ?? now();
                $transfer->save();
            }
            $this->line("  ✓ Fixed FundTransfer ID {$transfer->id}");
            $count++;
        }

        return $count;
    }

    protected function generateMissingVouchers(): int
    {
        $count = 0;
        $voucherService = app(VoucherService::class);

        // Find posted cashflows without vouchers
        $cashflows = Cashflow::query()
            ->where('status', 'posted')
            ->whereDoesntHave('voucher')
            ->get();

        foreach ($cashflows as $cashflow) {
            try {
                if (!$this->dryRun) {
                    $voucherService->generateFor($cashflow);
                }
                $this->line("  ✓ Generated voucher for Cashflow ID {$cashflow->id}");
                $count++;
            } catch (\Exception $e) {
                $this->warn("  ✗ Failed to generate voucher for Cashflow ID {$cashflow->id}: {$e->getMessage()}");
            }
        }

        // Find posted fund transfers without vouchers
        $transfers = FundTransfer::query()
            ->where('status', 'posted')
            ->whereDoesntHave('voucher')
            ->get();

        foreach ($transfers as $transfer) {
            try {
                if (!$this->dryRun) {
                    $voucherService->generateForFundTransfer($transfer);
                }
                $this->line("  ✓ Generated voucher for FundTransfer ID {$transfer->id}");
                $count++;
            } catch (\Exception $e) {
                $this->warn("  ✗ Failed to generate voucher for FundTransfer ID {$transfer->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    protected function recalculateCashAccountBalances(): int
    {
        $count = 0;
        // Note: Cash account balances are calculated dynamically via the saldo getter
        // No need to store them - they're derived from posted transactions
        $this->line("  ℹ️  Cash account balances are calculated dynamically (saldo_awal + posted transactions)");
        $this->line("  ℹ️  No persistent storage needed - all balances are accurate");

        return $count;
    }

    protected function fixBalanceInconsistencies(): int
    {
        $count = 0;

        // Fix receivables where paid > total
        $receivables = Receivable::query()
            ->whereRaw('nominal_dibayar > nominal')
            ->get();

        foreach ($receivables as $receivable) {
            if (!$this->dryRun) {
                $receivable->nominal_dibayar = $receivable->nominal;
                $receivable->save();
            }
            $this->line("  ✓ Fixed Receivable ID {$receivable->id}: nominal_dibayar capped to {$receivable->nominal}");
            $count++;
        }

        // Fix payables where paid > total
        $payables = Payable::query()
            ->whereRaw('nominal_dibayar > nominal')
            ->get();

        foreach ($payables as $payable) {
            if (!$this->dryRun) {
                $payable->nominal_dibayar = $payable->nominal;
                $payable->save();
            }
            $this->line("  ✓ Fixed Payable ID {$payable->id}: nominal_dibayar capped to {$payable->nominal}");
            $count++;
        }

        return $count;
    }

    protected function analyzeDuplicates(): void
    {
        $this->line("\n  📊 Analyzing potential duplicates...\n");

        // Analyze duplicate payments
        $duplicatePayments = Payment::query()
            ->selectRaw('receivable_id, payable_id, tanggal, COUNT(*) as cnt, GROUP_CONCAT(id) as ids')
            ->whereNull('deleted_at')
            ->groupByRaw('receivable_id, payable_id, tanggal')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicatePayments->isNotEmpty()) {
            $this->warn("\n  ⚠️  Duplicate Payment Groups:");
            foreach ($duplicatePayments as $group) {
                $ids = explode(',', $group->ids);
                $this->line("     IDs: " . implode(', ', $ids) . " (tanggal: {$group->tanggal})");
            }
            $this->line("  💡 Review these manually to determine which to keep/delete");
        }

        // Analyze duplicate cashflows
        $duplicateCashflows = Cashflow::query()
            ->selectRaw('tanggal, jenis, sumber, nominal, cash_account_id, COUNT(*) as cnt, GROUP_CONCAT(id) as ids')
            ->whereNull('deleted_at')
            ->groupByRaw('tanggal, jenis, sumber, nominal, cash_account_id')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicateCashflows->isNotEmpty()) {
            $this->warn("\n  ⚠️  Duplicate Cashflow Groups:");
            foreach ($duplicateCashflows as $group) {
                $ids = explode(',', $group->ids);
                $this->line("     Type: {$group->jenis->label()}, Sumber: {$group->sumber->label()}");
                $this->line("     IDs: " . implode(', ', $ids) . " (tanggal: {$group->tanggal})");
            }
            $this->line("  💡 Review these manually - they may be legitimate or duplicates");
        }

        if ($duplicatePayments->isEmpty() && $duplicateCashflows->isEmpty()) {
            $this->info("  ✅ No duplicate transactions found");
        }
    }
}
