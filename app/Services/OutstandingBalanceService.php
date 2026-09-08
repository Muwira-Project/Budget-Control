<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Support\Facades\DB;

/**
 * Centralized service for calculating outstanding balances (sisa)
 * for Payables and Receivables.
 */
class OutstandingBalanceService
{
    /**
     * Calculate outstanding balance for a specific party item and type.
     *
     * @param  string  $type  'ap' or 'ar'
     */
    public function calculate(string $type, int $pihakItemId): float
    {
        if (! in_array($type, ['ap', 'ar'], true)) {
            return 0.0;
        }

        if ($type === 'ap') {
            return (float) Payable::where('pihak_item_id', $pihakItemId)
                ->whereRaw('nominal - nominal_dibayar > 0')
                ->sum(DB::raw('nominal - nominal_dibayar'));
        }

        // AR
        return (float) Receivable::where('pihak_item_id', $pihakItemId)
            ->whereRaw('nominal - nominal_dibayar > 0')
            ->sum(DB::raw('nominal - nominal_dibayar'));
    }

    /**
     * Get outstanding balance for a Payable model.
     */
    public function forPayable(Payable $payable): float
    {
        return (float) ($payable->nominal - $payable->nominal_dibayar);
    }

    /**
     * Get outstanding balance for a Receivable model.
     */
    public function forReceivable(Receivable $receivable): float
    {
        return (float) ($receivable->nominal - $receivable->nominal_dibayar);
    }

    /**
     * Calculate total outstanding for all party items of a given type.
     *
     * @param  string  $type  'ap' or 'ar'
     * @return array<int, float> [pihak_item_id => outstanding]
     */
    public function calculateAll(string $type): array
    {
        if (! in_array($type, ['ap', 'ar'], true)) {
            return [];
        }

        if ($type === 'ap') {
            return Payable::whereRaw('nominal - nominal_dibayar > 0')
                ->selectRaw('pihak_item_id, SUM(nominal - nominal_dibayar) as outstanding')
                ->groupBy('pihak_item_id')
                ->pluck('outstanding', 'pihak_item_id')
                ->map(fn ($value) => (float) $value)
                ->toArray();
        }

        // AR
        return Receivable::whereRaw('nominal - nominal_dibayar > 0')
            ->selectRaw('pihak_item_id, SUM(nominal - nominal_dibayar) as outstanding')
            ->groupBy('pihak_item_id')
            ->pluck('outstanding', 'pihak_item_id')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }
}
