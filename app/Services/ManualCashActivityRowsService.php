<?php

namespace App\Services;

use App\Enums\KasStatus;
use App\Enums\PaymentJenis;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Cashflow;
use App\Models\MonitoringPeriod;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManualCashActivityRowsService
{
    /**
     * Query manual Posted Cashflow entries that have NOT been synced to Realisasi.
     *
     * Cashflows with project_id + pihak_item_id are auto-synced to Realisasi via
     * CashflowService::syncRealisasiFromCashflow() on post. Those already appear
     * as 'Actual Out' rows via the Realisasi query, so we EXCLUDE them here to
     * prevent double-counting.
     *
     * Cashflows WITHOUT project/pihak tag (pure operational entries, e.g. listrik
     * kantor entered manually without tagging) are the ones that are missing from
     * the export — this helper captures those.
     *
     * @param  Collection|array  $budgetNumbersByProject
     * @return array<int, array<string, mixed>>
     */
    public function rows(
        ?int $projectId,
        string $periodStart,
        string $periodEnd,
        string $periodLabel,
        string $projectNameFallback,
        string $poNumberFallback,
        $budgetNumbersByProject = [],
        string $budgetNumberFallback = '-'
    ): array {
        // IDs of cashflows that have already been synced to Realisasi (sumber = 'manual')
        $syncedCashflowIds = Realisasi::where('sumber', Realisasi::SUMBER_MANUAL)
            ->whereNotNull('sumber_id')
            ->pluck('sumber_id')
            ->all();

        $cashflows = Cashflow::query()
            ->with(['akun', 'project'])
            ->leftJoin('master_items as mi', 'mi.id', '=', 'cashflows.pihak_item_id')
            ->leftJoin('master_types as mt', 'mt.id', '=', 'cashflows.pihak_type_id')
            ->leftJoin('projects as prj', 'prj.id', '=', 'cashflows.project_id')
            ->where('cashflows.status', KasStatus::Posted)
            ->whereNull('cashflows.payment_id')           // manual entries only
            ->whereNotIn('cashflows.id', $syncedCashflowIds) // exclude already-in-Realisasi
            ->whereDate('cashflows.tanggal', '>=', $periodStart)
            ->whereDate('cashflows.tanggal', '<=', $periodEnd)
            ->when($projectId, fn ($q) => $q->where(fn ($sub) => $sub->where('cashflows.project_id', $projectId)->orWhereNull('cashflows.project_id')))
            ->select([
                'cashflows.id',
                'cashflows.tanggal',
                'cashflows.jenis',
                'cashflows.nominal',
                'cashflows.keterangan',
                'cashflows.akun_id',
                'cashflows.project_id',
                'mi.nama as party_name',
                'mt.nama as party_type',
                'prj.kode as project_code',
                'prj.nama as project_name',
                'prj.po_number',
                DB::raw('NULL as account_code'),
                DB::raw('NULL as account_name'),
            ])
            ->orderBy('cashflows.tanggal')
            ->get();

        $rows = [];
        foreach ($cashflows as $cf) {
            $dateObj = Carbon::parse($cf->tanggal);
            $dateStr = $dateObj->format('Y-m-d');
            $dayName = $dateObj->locale('id')->isoFormat('dddd');

            // Resolve akun label via eager-loaded relation
            $akun = Akun::find($cf->akun_id);
            $accountLabel = ($akun?->kode_akun ? '['.$akun->kode_akun.'] ' : '').($akun?->nama_akun ?? 'Cash Activity');

            $projectLabel = ($cf->project_code ? '['.$cf->project_code.'] ' : '').($cf->project_name ?? $projectNameFallback);
            $pihakLabel = ($cf->party_type && $cf->party_name) ? $cf->party_type.': '.$cf->party_name : ($cf->party_name ?? '-');
            $budgetNo = $cf->project_id ? ($budgetNumbersByProject[$cf->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $isMasuk = $cf->jenis->value === 'masuk';

            $rows[] = [
                'sort_date' => $dateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $cf->po_number ?? $poNumberFallback,
                'project' => $projectLabel,
                'account' => $accountLabel,
                'type' => $isMasuk ? 'Cash Activity (In)' : 'Cash Activity (Out)',
                'pihak' => $pihakLabel,
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => '',
                'budget' => 0.0,
                'actual_date' => $dateStr,
                'actual_out' => $isMasuk ? 0.0 : (float) $cf->nominal,
                'cash_in' => $isMasuk ? (float) $cf->nominal : 0.0,
                'ap_settlement' => 0.0,
                'variance' => 0.0,
                'description' => $cf->keterangan ?? '',
                '_akun_id' => $cf->akun_id,
            ];
        }

        return $rows;
    }

}
