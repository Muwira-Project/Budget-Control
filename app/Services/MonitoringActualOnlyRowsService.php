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

class MonitoringActualOnlyRowsService
{
    /**
     * Fallback: rows dari semua transaksi aktual (realisasi, Cash In, AP)
     * ketika tidak ada budget plan items yang overlap periode.
     *
     * @param  Collection|array  $budgetNumbersByProject
     * @return array<int, array<string, mixed>>
     */
    public function rows(
        MonitoringPeriod $period,
        ?int $projectId,
        string $periodStart,
        string $periodEnd,
        $budgetNumbersByProject = [],
        string $budgetNumberFallback = '-'
    ): array {
        $rows = [];
        $periodLabel = $period->nomor.' (W'.$period->week.')';

        // 1. Realisasi / Actual Out
        $realisasiRows = Realisasi::query()
            ->with(['akun', 'pihakType', 'pihakItem', 'project'])
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd)
            ->when($projectId, fn ($q) => $q->where(fn ($sub) => $sub->where('project_id', $projectId)->orWhereNull('project_id')))
            ->orderBy('tanggal')
            ->get();

        foreach ($realisasiRows as $r) {
            $actualDateStr = $r->tanggal->format('Y-m-d');
            $dayName = $r->tanggal->locale('id')->isoFormat('dddd');

            $projectLabel = ($r->project?->kode ? '['.$r->project->kode.'] ' : '').($r->project?->nama ?? '-');
            $accountLabel = ($r->akun?->kode_akun ? '['.$r->akun->kode_akun.'] ' : '').($r->akun?->nama_akun ?? '');

            $pihakType = $r->pihakType?->nama;
            $pihakItem = $r->pihakItem?->nama;
            $pihakLabel = ($pihakType && $pihakItem) ? $pihakType.': '.$pihakItem : ($pihakItem ?? '-');
            $budgetNo = $r->project_id ? ($budgetNumbersByProject[$r->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $actualDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $r->project?->po_number ?? '-',
                'project' => $projectLabel,
                'account' => $accountLabel,
                'type' => 'Actual Out',
                'pihak' => $pihakLabel,
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => '',
                'budget' => 0.0,
                'actual_date' => $actualDateStr,
                'actual_out' => (float) $r->nominal,
                'cash_in' => 0.0,
                'ap_settlement' => 0.0,
                'variance' => -(float) $r->nominal,
                'description' => $r->keterangan ?? '',
            ];
        }

        // 2. Cash In / AR Settlement (termasuk investor non-project)
        $cashInRows = Payment::query()
            ->join('receivables', 'receivables.id', '=', 'payments.receivable_id')
            ->leftJoin('master_items as mi', 'mi.id', '=', 'receivables.pihak_item_id')
            ->leftJoin('master_types as mt', 'mt.id', '=', 'receivables.pihak_type_id')
            ->leftJoin('projects as prj', 'prj.id', '=', 'receivables.project_id')
            ->where('payments.jenis', PaymentJenis::Masuk)
            ->whereDate('payments.tanggal', '>=', $periodStart)
            ->whereDate('payments.tanggal', '<=', $periodEnd)
            ->whereNotNull('payments.receivable_id')
            ->when($projectId, fn ($q) => $q->where(fn ($sub) => $sub->where('receivables.project_id', $projectId)->orWhereNull('receivables.project_id')))
            ->select([
                'payments.tanggal',
                'payments.nominal',
                'payments.keterangan',
                'receivables.project_id',
                'mi.nama as party_name',
                'mt.nama as party_type',
                'prj.kode as project_code',
                'prj.nama as project_name',
                'prj.po_number',
            ])
            ->orderBy('payments.tanggal')
            ->get();

        foreach ($cashInRows as $p) {
            $actualDateObj = Carbon::parse($p->tanggal);
            $actualDateStr = $actualDateObj->format('Y-m-d');
            $dayName = $actualDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($p->project_code ? '['.$p->project_code.'] ' : '').($p->project_name ?? '-');
            $pihakLabel = ($p->party_type && $p->party_name) ? $p->party_type.': '.$p->party_name : ($p->party_name ?? '-');
            $budgetNo = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $actualDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $p->po_number ?? '-',
                'project' => $projectLabel,
                'account' => 'Receivable / Cash In',
                'type' => 'Cash In (AR)',
                'pihak' => $pihakLabel,
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => '',
                'budget' => 0.0,
                'actual_date' => $actualDateStr,
                'actual_out' => 0.0,
                'cash_in' => (float) $p->nominal,
                'ap_settlement' => 0.0,
                'variance' => 0.0,
                'description' => $p->keterangan ?? '',
            ];
        }

        // 3. AP Settlement (termasuk hutang non-project, misal bayar listrik via AP)
        $apRows = Payment::query()
            ->join('payables', 'payables.id', '=', 'payments.payable_id')
            ->leftJoin('akuns', 'akuns.id', '=', 'payables.akun_id')
            ->leftJoin('master_items as mi', 'mi.id', '=', 'payables.pihak_item_id')
            ->leftJoin('master_types as mt', 'mt.id', '=', 'payables.pihak_type_id')
            ->leftJoin('projects as prj', 'prj.id', '=', 'payables.project_id')
            ->where('payments.jenis', PaymentJenis::Keluar)
            ->whereDate('payments.tanggal', '>=', $periodStart)
            ->whereDate('payments.tanggal', '<=', $periodEnd)
            ->whereNotNull('payments.payable_id')
            ->when($projectId, fn ($q) => $q->where(fn ($sub) => $sub->where('payables.project_id', $projectId)->orWhereNull('payables.project_id')))
            ->select([
                'payments.tanggal',
                'payments.nominal',
                'payments.keterangan',
                'payables.akun_id',
                'payables.project_id',
                'akuns.kode_akun as account_code',
                'akuns.nama_akun as account_name',
                'mi.nama as party_name',
                'mt.nama as party_type',
                'prj.kode as project_code',
                'prj.nama as project_name',
                'prj.po_number',
            ])
            ->orderBy('payments.tanggal')
            ->get();

        foreach ($apRows as $p) {
            $actualDateObj = Carbon::parse($p->tanggal);
            $actualDateStr = $actualDateObj->format('Y-m-d');
            $dayName = $actualDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($p->project_code ? '['.$p->project_code.'] ' : '').($p->project_name ?? '-');
            $accountLabel = ($p->account_code ? '['.$p->account_code.'] ' : '').($p->account_name ?? 'Payable / Cash Out');
            $pihakLabel = ($p->party_type && $p->party_name) ? $p->party_type.': '.$p->party_name : ($p->party_name ?? '-');
            $budgetNo = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $actualDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $p->po_number ?? '-',
                'project' => $projectLabel,
                'account' => $accountLabel,
                'type' => 'AP Settlement',
                'pihak' => $pihakLabel,
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => '',
                'budget' => 0.0,
                'actual_date' => $actualDateStr,
                'actual_out' => 0.0,
                'cash_in' => 0.0,
                'ap_settlement' => (float) $p->nominal,
                'variance' => 0.0,
                'description' => $p->keterangan ?? '',
            ];
        }

        // Cash Activity rows — manual Posted cashflows yang TIDAK ter-sync ke Realisasi
        foreach (app(ManualCashActivityRowsService::class)->rows($projectId, $periodStart, $periodEnd, $periodLabel, 'All Projects', '-', $budgetNumbersByProject, $budgetNumberFallback) as $cr) {
            $rows[] = $cr;
        }

        usort($rows, fn ($a, $b) => [$a['sort_date'], $a['project'], $a['account'], $a['type']]
            <=>
            [$b['sort_date'], $b['project'], $b['account'], $b['type']]
        );

        return $rows;
    }

}
