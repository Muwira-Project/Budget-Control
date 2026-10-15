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

class MonitoringVarianceDetailService
{
    /**
     * Detail rows for variance export: one row per transaction (budget item, realisasi, payment).
     * Each row has consolidated fields: budget_no, po_number, project, account, type, pihak, periode_week, day_name, budget_date, budget, actual_date, actual_out, cash_in, ap_settlement, variance, description.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(MonitoringPeriod $period, bool $includeLegacy): array
    {
        if (! $period->tanggal_mulai || ! $period->tanggal_selesai) {
            return [];
        }

        $projectId = $period->project_id;
        $periodStart = $period->tanggal_mulai->format('Y-m-d');
        $periodEnd = $period->tanggal_selesai->format('Y-m-d');
        $periodLabel = $period->nomor.' (W'.$period->week.')';

        $budgetNumbersByProject = BudgetPlan::query()
            ->whereNotNull('nomor')
            ->whereNotNull('project_id')
            ->orderBy('periode')
            ->get()
            ->groupBy('project_id')
            ->map(fn ($plans) => $plans->pluck('nomor')->filter()->unique()->implode(', '));

        $budgetNumberFallback = $period->budget_number ?: ($projectId ? ($budgetNumbersByProject[$projectId] ?? '-') : '-');

        // ── 1. Budget plan items yang overlap dengan periode ─────────────────
        $budgetItems = BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->join('akuns', 'akuns.id', '=', 'budget_plan_items.akun_id')
            ->leftJoin('projects', 'projects.id', '=', 'budget_plans.project_id')
            ->when($projectId, fn ($q) => $q->where('budget_plans.project_id', $projectId))
            ->where(function ($q) use ($periodStart, $periodEnd, $includeLegacy) {
                $q->where(function ($dated) use ($periodStart, $periodEnd) {
                    $dated->whereNotNull('budget_plan_items.tanggal_mulai')
                        ->whereNotNull('budget_plan_items.tanggal_selesai')
                        ->whereDate('budget_plan_items.tanggal_mulai', '<=', $periodEnd)
                        ->whereDate('budget_plan_items.tanggal_selesai', '>=', $periodStart);
                });
                if ($includeLegacy) {
                    $q->orWhere(function ($legacy) {
                        $legacy->whereNull('budget_plan_items.tanggal_mulai')
                            ->orWhereNull('budget_plan_items.tanggal_selesai');
                    });
                }
            })
            ->select([
                'budget_plan_items.id as item_id',
                'budget_plans.project_id',
                'budget_plans.nomor as budget_number',
                'budget_plan_items.akun_id',
                'budget_plan_items.nominal as budget',
                'budget_plan_items.tanggal_mulai',
                'budget_plan_items.tanggal_selesai',
                'akuns.kode_akun as account_code',
                'akuns.nama_akun as account_name',
                'projects.kode as project_code',
                'projects.nama as project_name',
                'projects.po_number',
            ])
            ->get();

        if ($budgetItems->isEmpty()) {
            return app(MonitoringActualOnlyRowsService::class)->rows($period, $projectId, $periodStart, $periodEnd, $budgetNumbersByProject, $budgetNumberFallback);
        }

        // Kumpulkan budget & actual per akun untuk variance summary
        $budgetByAkun = [];
        $actualByAkun = [];

        foreach ($budgetItems as $item) {
            $budgetByAkun[$item->akun_id] = ($budgetByAkun[$item->akun_id] ?? 0) + (float) $item->budget;
        }

        // ── 2. Actual Out (Realisasi) dalam periode ──────────────────────────
        // NOTE: Tidak dibatasi ke akun yang ada di budget — realisasi non-project
        // (misal bayar listrik, biaya operasional) harus tetap masuk ke export.
        $realisasiRows = Realisasi::query()
            ->with(['project', 'pihakType', 'pihakItem', 'akun'])
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd)
            ->when($projectId, fn ($q) => $q->where(fn ($sub) => $sub->where('project_id', $projectId)->orWhereNull('project_id')))
            ->orderBy('tanggal')
            ->get();

        // ── 3. Cash In / AR Settlement dalam periode ─────────────────────────
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

        // ── 4. Account Payable (Tagihan Masuk) & AP Payment dalam periode ───────
        // A. Tagihan Hutang (Payables) yang timbul di dalam periode
        $payableRows = Payable::query()
            ->leftJoin('realisasi as rls', 'rls.id', '=', 'payables.realisasi_id')
            ->leftJoin('akuns as ak_p', 'ak_p.id', '=', 'payables.akun_id')
            ->leftJoin('akuns as ak_r', 'ak_r.id', '=', 'rls.akun_id')
            ->leftJoin('master_items as mi_p', 'mi_p.id', '=', 'payables.pihak_item_id')
            ->leftJoin('master_items as mi_r', 'mi_r.id', '=', 'rls.pihak_item_id')
            ->leftJoin('master_types as mt_p', 'mt_p.id', '=', 'payables.pihak_type_id')
            ->leftJoin('master_types as mt_r', 'mt_r.id', '=', 'rls.pihak_type_id')
            ->leftJoin('projects as prj_p', 'prj_p.id', '=', 'payables.project_id')
            ->leftJoin('projects as prj_r', 'prj_r.id', '=', 'rls.project_id')
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->where(function ($d) use ($periodStart, $periodEnd) {
                    $d->whereNotNull('payables.tanggal')
                        ->whereDate('payables.tanggal', '>=', $periodStart)
                        ->whereDate('payables.tanggal', '<=', $periodEnd);
                })
                    ->orWhere(function ($d) use ($periodStart, $periodEnd) {
                        $d->whereNull('payables.tanggal')
                            ->whereNotNull('payables.jatuh_tempo')
                            ->whereDate('payables.jatuh_tempo', '>=', $periodStart)
                            ->whereDate('payables.jatuh_tempo', '<=', $periodEnd);
                    })
                    ->orWhere(function ($d) use ($periodStart, $periodEnd) {
                        $d->whereNull('payables.tanggal')
                            ->whereNull('payables.jatuh_tempo')
                            ->whereNotNull('rls.tanggal')
                            ->whereDate('rls.tanggal', '>=', $periodStart)
                            ->whereDate('rls.tanggal', '<=', $periodEnd);
                    });
            })
            ->when($projectId, fn ($q) => $q->where(function ($sub) use ($projectId) {
                $sub->where('payables.project_id', $projectId)
                    ->orWhere('rls.project_id', $projectId)
                    ->orWhere(function ($nullScope) {
                        $nullScope->whereNull('payables.project_id')
                            ->whereNull('rls.project_id');
                    });
            }))
            ->select([
                'payables.id',
                DB::raw('COALESCE(payables.tanggal, payables.jatuh_tempo, rls.tanggal) as tanggal'),
                'payables.nominal',
                'payables.nominal_dibayar',
                'payables.keterangan',
                'payables.nomor_invoice',
                DB::raw('COALESCE(payables.project_id, rls.project_id) as project_id'),
                DB::raw('COALESCE(ak_p.kode_akun, ak_r.kode_akun) as account_code'),
                DB::raw('COALESCE(ak_p.nama_akun, ak_r.nama_akun) as account_name'),
                DB::raw('COALESCE(mi_p.nama, mi_r.nama) as party_name'),
                DB::raw('COALESCE(mt_p.nama, mt_r.nama) as party_type'),
                DB::raw('COALESCE(prj_p.kode, prj_r.kode) as project_code'),
                DB::raw('COALESCE(prj_p.nama, prj_r.nama) as project_name'),
                DB::raw('COALESCE(prj_p.po_number, prj_r.po_number) as po_number'),
            ])
            ->orderBy('payables.tanggal')
            ->get();

        // ── 5. Susun baris output ────────────────────────────────────────────
        $projectFallback = Project::find($projectId);
        $projectCodeFallback = $projectFallback?->kode ?? ($projectId ? 'PRJ-'.$projectId : 'GLOBAL');
        $projectNameFallback = $projectFallback?->nama ?? ($projectId ? 'Project '.$projectId : '-');
        $poNumberFallback = $projectFallback?->po_number ?? '-';

        $rows = [];

        // E. Cash Activity rows — manual Posted cashflows yang TIDAK ter-sync ke Realisasi
        $cashActivityRows = app(ManualCashActivityRowsService::class)->rows($projectId, $periodStart, $periodEnd, $periodLabel, $projectNameFallback, $poNumberFallback, $budgetNumbersByProject, $budgetNumberFallback);

        // Akumulasikan realisasi ke actualByAkun
        foreach ($realisasiRows as $r) {
            $actualByAkun[$r->akun_id] = ($actualByAkun[$r->akun_id] ?? 0) + (float) $r->nominal;
        }

        // Akumulasikan juga cashflow keluar ke actualByAkun agar variance budget row memperhitungkan pengeluaran kas
        foreach ($cashActivityRows as $cr) {
            if ($cr['type'] === 'Cash Activity (Out)' && ! empty($cr['_akun_id'])) {
                $actualByAkun[$cr['_akun_id']] = ($actualByAkun[$cr['_akun_id']] ?? 0) + (float) $cr['actual_out'];
            }
        }

        // Budget accounts list to detect unbudgeted actual expenses
        $budgetAkunIds = $budgetItems->pluck('akun_id')->unique()->all();

        // A. Budget rows — satu baris per budget plan item
        $budgetRows = [];
        foreach ($budgetItems as $item) {
            $budgetDateObj = $item->tanggal_mulai ? Carbon::parse($item->tanggal_mulai) : $period->tanggal_mulai;
            $budgetDateStr = $budgetDateObj->format('Y-m-d');
            $dayName = $budgetDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($item->project_code ? '['.$item->project_code.'] ' : '').($item->project_name ?? $projectNameFallback);
            $accountLabel = ($item->account_code ? '['.$item->account_code.'] ' : '').($item->account_name ?? '');

            $totalBudgetForAkun = (float) $item->budget;
            $totalActualForAkun = $actualByAkun[$item->akun_id] ?? 0.0;

            $budgetNo = $item->budget_number ?: ($item->project_id ? ($budgetNumbersByProject[$item->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback);

            $budgetRows[] = [
                'sort_date' => $budgetDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $item->po_number ?? $poNumberFallback,
                'project' => $projectLabel,
                'account' => $accountLabel,
                'type' => 'Budget',
                'pihak' => '-',
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => $budgetDateStr,
                'budget' => $totalBudgetForAkun,
                'actual_date' => '',
                'actual_out' => 0.0,
                'cash_in' => 0.0,
                'ap_settlement' => 0.0,
                'variance' => $totalBudgetForAkun - $totalActualForAkun,
                'description' => 'Rencana Anggaran '.($item->tanggal_mulai ? Carbon::parse($item->tanggal_mulai)->format('d/m/Y').' – '.(Carbon::parse($item->tanggal_selesai)->format('d/m/Y') ?? '') : $period->periode_label),
            ];
        }

        foreach ($budgetRows as $br) {
            $rows[] = $br;
        }

        // B. Actual Out rows — satu baris per realisasi (termasuk non-project)
        foreach ($realisasiRows as $r) {
            $akunInfo = $budgetItems->firstWhere('akun_id', $r->akun_id);
            $actualDateStr = $r->tanggal->format('Y-m-d');
            $dayName = $r->tanggal->locale('id')->isoFormat('dddd');

            $projectLabel = ($r->project?->kode ? '['.$r->project->kode.'] ' : '').($r->project?->nama ?? '-');
            $accountCode = $akunInfo?->account_code ?? $r->akun?->kode_akun ?? '';
            $accountName = $akunInfo?->account_name ?? $r->akun?->nama_akun ?? '';
            $accountLabel = ($accountCode ? '['.$accountCode.'] ' : '').$accountName;

            $pihakType = $r->pihakType?->nama;
            $pihakItem = $r->pihakItem?->nama;
            $pihakLabel = ($pihakType && $pihakItem) ? $pihakType.': '.$pihakItem : ($pihakItem ?? '-');

            $isUnbudgeted = ! in_array($r->akun_id, $budgetAkunIds, true);
            $budgetNo = $r->project_id ? ($budgetNumbersByProject[$r->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $actualDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $r->project?->po_number ?? $poNumberFallback,
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
                'variance' => $isUnbudgeted ? -(float) $r->nominal : 0.0,
                'description' => $r->keterangan ?? '',
            ];
        }

        // C. Cash In / AR Settlement
        foreach ($cashInRows as $p) {
            $actualDateObj = Carbon::parse($p->tanggal);
            $actualDateStr = $actualDateObj->format('Y-m-d');
            $dayName = $actualDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($p->project_code ? '['.$p->project_code.'] ' : '').($p->project_name ?? $projectNameFallback);
            $pihakLabel = ($p->party_type && $p->party_name) ? $p->party_type.': '.$p->party_name : ($p->party_name ?? '-');
            $budgetNo = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $actualDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $p->po_number ?? $poNumberFallback,
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

        // D1. Account Payable (Tagihan Hutang Masuk)
        foreach ($payableRows as $ap) {
            $apDateObj = Carbon::parse($ap->tanggal);
            $apDateStr = $apDateObj->format('Y-m-d');
            $dayName = $apDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($ap->project_code ? '['.$ap->project_code.'] ' : '').($ap->project_name ?? $projectNameFallback);
            $accountLabel = ($ap->account_code ? '['.$ap->account_code.'] ' : '').($ap->account_name ?? 'Account Payable');
            $pihakLabel = ($ap->party_type && $ap->party_name) ? $ap->party_type.': '.$ap->party_name : ($ap->party_name ?? '-');

            $paid = (float) ($ap->nominal_dibayar ?? 0);
            $total = (float) $ap->nominal;
            $statusLabel = $paid <= 0 ? 'Belum Bayar' : ($paid >= $total ? 'Lunas' : 'Sebagian');
            $budgetNo = $ap->project_id ? ($budgetNumbersByProject[$ap->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date' => $apDateStr,
                'budget_no' => $budgetNo ?: '-',
                'po_number' => $ap->po_number ?? $poNumberFallback,
                'project' => $projectLabel,
                'account' => $accountLabel,
                'type' => 'Account Payable (AP)',
                'pihak' => $pihakLabel,
                'periode_week' => $periodLabel,
                'day_name' => $dayName,
                'budget_date' => '',
                'budget' => 0.0,
                'actual_date' => $apDateStr,
                'actual_out' => 0.0,
                'cash_in' => 0.0,
                'ap_settlement' => (float) $ap->nominal,
                'variance' => 0.0,
                'description' => ($ap->keterangan ? $ap->keterangan.' ' : '').'[Status: '.$statusLabel.']',
            ];
        }

        // E. Cash Activity rows
        foreach ($cashActivityRows as $cr) {
            $isUnbudgeted = ! empty($cr['_akun_id']) && ! in_array($cr['_akun_id'], $budgetAkunIds, true);
            if ($cr['type'] === 'Cash Activity (Out)' && $isUnbudgeted) {
                $cr['variance'] = -(float) $cr['actual_out'];
            }
            unset($cr['_akun_id']);
            $rows[] = $cr;
        }

        // Sort: sort_date → project → account → type
        usort($rows, fn ($a, $b) => [$a['sort_date'], $a['project'], $a['account'], $a['type']]
            <=>
            [$b['sort_date'], $b['project'], $b['account'], $b['type']]
        );

        return $rows;
    }

}
