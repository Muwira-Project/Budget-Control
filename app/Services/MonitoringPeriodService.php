<?php

namespace App\Services;

use App\Enums\KasStatus;
use App\Enums\PaymentJenis;
use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Cashflow;
use App\Models\MonitoringPeriod;
use App\Models\NumberSequence;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringPeriodService
{
    /**
     * Create a new monitoring period.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MonitoringPeriod
    {
        return MonitoringPeriod::create([
            'nomor' => $this->nextNomor(),
            'project_id' => $data['project_id'] ?? null,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);
    }

    /**
     * Update an existing monitoring period.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MonitoringPeriod $period, array $data): MonitoringPeriod
    {
        $period->update([
            'project_id' => $data['project_id'] ?? null,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);

        return $period->refresh();
    }

    /**
     * Delete a monitoring period.
     */
    public function delete(MonitoringPeriod $period): void
    {
        $period->delete();
    }

    /**
     * List monitoring periods, optionally filtered by project and search.
     */
    public function paginate(?int $projectId = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return MonitoringPeriod::query()
            ->with('project')
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($search !== '', fn($query) => $query->where('nomor', 'like', '%' . $search . '%'))
            ->orderByDesc('tanggal_mulai')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Per-account monitoring rows: budget, actual, and variance within the period.
     *
     * @return Collection<int, array{akun: Akun, budget: float, actual: float, variance: float}>
     */
    public function accountBreakdown(MonitoringPeriod $period): Collection
    {
        $budgets = $this->budgetPerAccount($period);
        $actuals = $this->actualPerAccount($period);

        $akunIds = $budgets->keys()->merge($actuals->keys())->unique()->values();

        return Akun::query()
            ->whereIn('id', $akunIds)
            ->orderBy('kode_akun')
            ->get()
            ->map(function (Akun $akun) use ($budgets, $actuals) {
                $budget = (float) ($budgets[$akun->id] ?? 0);
                $actual = (float) ($actuals[$akun->id] ?? 0);

                return [
                    'akun' => $akun,
                    'budget' => $budget,
                    'actual' => $actual,
                    'variance' => $budget - $actual,
                ];
            })
            ->values();
    }

    /**
     * Detail rows for variance export: one row per transaction (budget item, realisasi, payment).
     * Each row has consolidated fields: budget_no, po_number, project, account, type, pihak, periode_week, day_name, budget_date, budget, actual_date, actual_out, cash_in, ap_settlement, variance, description.
     *
     * @return array<int, array<string, mixed>>
     */
    public function varianceDetailRows(MonitoringPeriod $period): array
    {
        if (! $period->tanggal_mulai || ! $period->tanggal_selesai) {
            return [];
        }

        $projectId     = $period->project_id;
        $periodStart   = $period->tanggal_mulai->format('Y-m-d');
        $periodEnd     = $period->tanggal_selesai->format('Y-m-d');
        $includeLegacy = ! $this->hasEarlierPeriodInSameMonth($period);
        $periodLabel   = $period->nomor . ' (W' . $period->week . ')';

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
            return $this->varianceRowsFromActualOnly($period, $projectId, $periodStart, $periodEnd, $budgetNumbersByProject, $budgetNumberFallback);
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

        // B. Pelunasan Hutang (Payments untuk Payable) yang dibayar di dalam periode
        $apPaymentRows = Payment::query()
            ->join('payables', 'payables.id', '=', 'payments.payable_id')
            ->leftJoin('realisasi as rls', 'rls.id', '=', 'payables.realisasi_id')
            ->leftJoin('akuns as ak_p', 'ak_p.id', '=', 'payables.akun_id')
            ->leftJoin('akuns as ak_r', 'ak_r.id', '=', 'rls.akun_id')
            ->leftJoin('master_items as mi_p', 'mi_p.id', '=', 'payables.pihak_item_id')
            ->leftJoin('master_items as mi_r', 'mi_r.id', '=', 'rls.pihak_item_id')
            ->leftJoin('master_types as mt_p', 'mt_p.id', '=', 'payables.pihak_type_id')
            ->leftJoin('master_types as mt_r', 'mt_r.id', '=', 'rls.pihak_type_id')
            ->leftJoin('projects as prj_p', 'prj_p.id', '=', 'payables.project_id')
            ->leftJoin('projects as prj_r', 'prj_r.id', '=', 'rls.project_id')
            ->where('payments.jenis', PaymentJenis::Keluar)
            ->whereDate('payments.tanggal', '>=', $periodStart)
            ->whereDate('payments.tanggal', '<=', $periodEnd)
            ->whereNotNull('payments.payable_id')
            ->when($projectId, fn ($q) => $q->where(function ($sub) use ($projectId) {
                $sub->where('payables.project_id', $projectId)
                    ->orWhere('rls.project_id', $projectId)
                    ->orWhere(function ($nullScope) {
                        $nullScope->whereNull('payables.project_id')
                            ->whereNull('rls.project_id');
                    });
            }))
            ->select([
                'payments.tanggal',
                'payments.nominal',
                'payments.keterangan',
                DB::raw('COALESCE(payables.project_id, rls.project_id) as project_id'),
                DB::raw('COALESCE(ak_p.kode_akun, ak_r.kode_akun) as account_code'),
                DB::raw('COALESCE(ak_p.nama_akun, ak_r.nama_akun) as account_name'),
                DB::raw('COALESCE(mi_p.nama, mi_r.nama) as party_name'),
                DB::raw('COALESCE(mt_p.nama, mt_r.nama) as party_type'),
                DB::raw('COALESCE(prj_p.kode, prj_r.kode) as project_code'),
                DB::raw('COALESCE(prj_p.nama, prj_r.nama) as project_name'),
                DB::raw('COALESCE(prj_p.po_number, prj_r.po_number) as po_number'),
            ])
            ->orderBy('payments.tanggal')
            ->get();

        // ── 5. Susun baris output ────────────────────────────────────────────
        $projectFallback = Project::find($projectId);
        $projectCodeFallback = $projectFallback?->kode ?? ($projectId ? 'PRJ-' . $projectId : 'GLOBAL');
        $projectNameFallback = $projectFallback?->nama ?? ($projectId ? 'Project ' . $projectId : 'All Projects');
        $poNumberFallback = $projectFallback?->po_number ?? '-';

        $rows = [];

        // E. Cash Activity rows — manual Posted cashflows yang TIDAK ter-sync ke Realisasi
        $cashActivityRows = $this->getCashActivityRows($projectId, $periodStart, $periodEnd, $periodLabel, $projectNameFallback, $poNumberFallback, $budgetNumbersByProject, $budgetNumberFallback);

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

            $projectLabel = ($item->project_code ? '[' . $item->project_code . '] ' : '') . ($item->project_name ?? $projectNameFallback);
            $accountLabel = ($item->account_code ? '[' . $item->account_code . '] ' : '') . ($item->account_name ?? '');

            $totalBudgetForAkun = (float) $item->budget;
            $totalActualForAkun = $actualByAkun[$item->akun_id] ?? 0.0;

            $budgetNo = $item->budget_number ?: ($item->project_id ? ($budgetNumbersByProject[$item->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback);

            $budgetRows[] = [
                'sort_date'     => $budgetDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $item->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'Budget',
                'pihak'         => '-',
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => $budgetDateStr,
                'budget'        => $totalBudgetForAkun,
                'actual_date'   => '',
                'actual_out'    => 0.0,
                'cash_in'       => 0.0,
                'ap_settlement' => 0.0,
                'variance'      => $totalBudgetForAkun - $totalActualForAkun,
                'description'   => 'Rencana Anggaran ' . ($item->tanggal_mulai ? Carbon::parse($item->tanggal_mulai)->format('d/m/Y') . ' – ' . (Carbon::parse($item->tanggal_selesai)->format('d/m/Y') ?? '') : $period->periode_label),
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

            $projectLabel = ($r->project?->kode ? '[' . $r->project->kode . '] ' : '') . ($r->project?->nama ?? 'Non-Project');
            $accountCode  = $akunInfo?->account_code ?? $r->akun?->kode_akun ?? '';
            $accountName  = $akunInfo?->account_name ?? $r->akun?->nama_akun ?? '';
            $accountLabel = ($accountCode ? '[' . $accountCode . '] ' : '') . $accountName;

            $pihakType = $r->pihakType?->nama;
            $pihakItem = $r->pihakItem?->nama;
            $pihakLabel = ($pihakType && $pihakItem) ? $pihakType . ': ' . $pihakItem : ($pihakItem ?? '-');

            $isUnbudgeted = ! in_array($r->akun_id, $budgetAkunIds, true);
            $budgetNo = $r->project_id ? ($budgetNumbersByProject[$r->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $r->project?->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'Actual Out',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => (float) $r->nominal,
                'cash_in'       => 0.0,
                'ap_settlement' => 0.0,
                'variance'      => $isUnbudgeted ? -(float) $r->nominal : 0.0,
                'description'   => $r->keterangan ?? '',
            ];
        }

        // C. Cash In / AR Settlement
        foreach ($cashInRows as $p) {
            $actualDateObj = Carbon::parse($p->tanggal);
            $actualDateStr = $actualDateObj->format('Y-m-d');
            $dayName = $actualDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($p->project_code ? '[' . $p->project_code . '] ' : '') . ($p->project_name ?? $projectNameFallback);
            $pihakLabel   = ($p->party_type && $p->party_name) ? $p->party_type . ': ' . $p->party_name : ($p->party_name ?? '-');
            $budgetNo     = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $p->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => 'Receivable / Cash In',
                'type'          => 'Cash In (AR)',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => 0.0,
                'cash_in'       => (float) $p->nominal,
                'ap_settlement' => 0.0,
                'variance'      => 0.0,
                'description'   => $p->keterangan ?? '',
            ];
        }

        // D1. Account Payable (Tagihan Hutang Masuk)
        foreach ($payableRows as $ap) {
            $apDateObj = Carbon::parse($ap->tanggal);
            $apDateStr = $apDateObj->format('Y-m-d');
            $dayName   = $apDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($ap->project_code ? '[' . $ap->project_code . '] ' : '') . ($ap->project_name ?? $projectNameFallback);
            $accountLabel = ($ap->account_code ? '[' . $ap->account_code . '] ' : '') . ($ap->account_name ?? 'Account Payable');
            $pihakLabel   = ($ap->party_type && $ap->party_name) ? $ap->party_type . ': ' . $ap->party_name : ($ap->party_name ?? '-');

            $paid = (float) ($ap->nominal_dibayar ?? 0);
            $total = (float) $ap->nominal;
            $statusLabel = $paid <= 0 ? 'Belum Bayar' : ($paid >= $total ? 'Lunas' : 'Sebagian');
            $budgetNo    = $ap->project_id ? ($budgetNumbersByProject[$ap->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $apDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $ap->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'Account Payable (AP)',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $apDateStr,
                'actual_out'    => 0.0,
                'cash_in'       => 0.0,
                'ap_settlement' => (float) $ap->nominal,
                'variance'      => 0.0,
                'description'   => ($ap->keterangan ? $ap->keterangan . ' ' : '') . '[Status: ' . $statusLabel . ']',
            ];
        }

        // D2. AP Payment (Pelunasan Hutang via Kas)
        foreach ($apPaymentRows as $p) {
            $actualDateObj = Carbon::parse($p->tanggal);
            $actualDateStr = $actualDateObj->format('Y-m-d');
            $dayName = $actualDateObj->locale('id')->isoFormat('dddd');

            $projectLabel = ($p->project_code ? '[' . $p->project_code . '] ' : '') . ($p->project_name ?? $projectNameFallback);
            $accountLabel = ($p->account_code ? '[' . $p->account_code . '] ' : '') . ($p->account_name ?? 'Payable / Cash Out');
            $pihakLabel   = ($p->party_type && $p->party_name) ? $p->party_type . ': ' . $p->party_name : ($p->party_name ?? '-');
            $budgetNo     = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $p->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'AP Payment',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => 0.0,
                'cash_in'       => 0.0,
                'ap_settlement' => (float) $p->nominal,
                'variance'      => 0.0,
                'description'   => $p->keterangan ?? 'Pelunasan Hutang',
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
        usort($rows, fn ($a, $b) =>
            [$a['sort_date'], $a['project'], $a['account'], $a['type']]
            <=>
            [$b['sort_date'], $b['project'], $b['account'], $b['type']]
        );

        return $rows;
    }

    /**
     * Fallback: rows dari semua transaksi aktual (realisasi, Cash In, AP)
     * ketika tidak ada budget plan items yang overlap periode.
     *
     * @param  \Illuminate\Support\Collection|array  $budgetNumbersByProject
     * @return array<int, array<string, mixed>>
     */
    private function varianceRowsFromActualOnly(
        MonitoringPeriod $period,
        ?int $projectId,
        string $periodStart,
        string $periodEnd,
        $budgetNumbersByProject = [],
        string $budgetNumberFallback = '-'
    ): array {
        $rows = [];
        $periodLabel = $period->nomor . ' (W' . $period->week . ')';

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

            $projectLabel = ($r->project?->kode ? '[' . $r->project->kode . '] ' : '') . ($r->project?->nama ?? 'Non-Project');
            $accountLabel = ($r->akun?->kode_akun ? '[' . $r->akun->kode_akun . '] ' : '') . ($r->akun?->nama_akun ?? '');

            $pihakType = $r->pihakType?->nama;
            $pihakItem = $r->pihakItem?->nama;
            $pihakLabel = ($pihakType && $pihakItem) ? $pihakType . ': ' . $pihakItem : ($pihakItem ?? '-');
            $budgetNo   = $r->project_id ? ($budgetNumbersByProject[$r->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $r->project?->po_number ?? '-',
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'Actual Out',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => (float) $r->nominal,
                'cash_in'       => 0.0,
                'ap_settlement' => 0.0,
                'variance'      => -(float) $r->nominal,
                'description'   => $r->keterangan ?? '',
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

            $projectLabel = ($p->project_code ? '[' . $p->project_code . '] ' : '') . ($p->project_name ?? 'Non-Project');
            $pihakLabel   = ($p->party_type && $p->party_name) ? $p->party_type . ': ' . $p->party_name : ($p->party_name ?? '-');
            $budgetNo     = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $p->po_number ?? '-',
                'project'       => $projectLabel,
                'account'       => 'Receivable / Cash In',
                'type'          => 'Cash In (AR)',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => 0.0,
                'cash_in'       => (float) $p->nominal,
                'ap_settlement' => 0.0,
                'variance'      => 0.0,
                'description'   => $p->keterangan ?? '',
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

            $projectLabel = ($p->project_code ? '[' . $p->project_code . '] ' : '') . ($p->project_name ?? 'Non-Project');
            $accountLabel = ($p->account_code ? '[' . $p->account_code . '] ' : '') . ($p->account_name ?? 'Payable / Cash Out');
            $pihakLabel   = ($p->party_type && $p->party_name) ? $p->party_type . ': ' . $p->party_name : ($p->party_name ?? '-');
            $budgetNo     = $p->project_id ? ($budgetNumbersByProject[$p->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $rows[] = [
                'sort_date'     => $actualDateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $p->po_number ?? '-',
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => 'AP Settlement',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $actualDateStr,
                'actual_out'    => 0.0,
                'cash_in'       => 0.0,
                'ap_settlement' => (float) $p->nominal,
                'variance'      => 0.0,
                'description'   => $p->keterangan ?? '',
            ];
        }

        // Cash Activity rows — manual Posted cashflows yang TIDAK ter-sync ke Realisasi
        foreach ($this->getCashActivityRows($projectId, $periodStart, $periodEnd, $periodLabel, 'All Projects', '-', $budgetNumbersByProject, $budgetNumberFallback) as $cr) {
            $rows[] = $cr;
        }

        usort($rows, fn ($a, $b) =>
            [$a['sort_date'], $a['project'], $a['account'], $a['type']]
            <=>
            [$b['sort_date'], $b['project'], $b['account'], $b['type']]
        );

        return $rows;
    }

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
     * @param  \Illuminate\Support\Collection|array  $budgetNumbersByProject
     * @return array<int, array<string, mixed>>
     */
    private function getCashActivityRows(
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
            $dateObj  = Carbon::parse($cf->tanggal);
            $dateStr  = $dateObj->format('Y-m-d');
            $dayName  = $dateObj->locale('id')->isoFormat('dddd');

            // Resolve akun label via eager-loaded relation
            $akun = Akun::find($cf->akun_id);
            $accountLabel = ($akun?->kode_akun ? '[' . $akun->kode_akun . '] ' : '') . ($akun?->nama_akun ?? 'Cash Activity');

            $projectLabel = ($cf->project_code ? '[' . $cf->project_code . '] ' : '') . ($cf->project_name ?? $projectNameFallback);
            $pihakLabel   = ($cf->party_type && $cf->party_name) ? $cf->party_type . ': ' . $cf->party_name : ($cf->party_name ?? '-');
            $budgetNo     = $cf->project_id ? ($budgetNumbersByProject[$cf->project_id] ?? $budgetNumberFallback) : $budgetNumberFallback;

            $isMasuk = $cf->jenis->value === 'masuk';

            $rows[] = [
                'sort_date'     => $dateStr,
                'budget_no'     => $budgetNo ?: '-',
                'po_number'     => $cf->po_number ?? $poNumberFallback,
                'project'       => $projectLabel,
                'account'       => $accountLabel,
                'type'          => $isMasuk ? 'Cash Activity (In)' : 'Cash Activity (Out)',
                'pihak'         => $pihakLabel,
                'periode_week'  => $periodLabel,
                'day_name'      => $dayName,
                'budget_date'   => '',
                'budget'        => 0.0,
                'actual_date'   => $dateStr,
                'actual_out'    => $isMasuk ? 0.0 : (float) $cf->nominal,
                'cash_in'       => $isMasuk ? (float) $cf->nominal : 0.0,
                'ap_settlement' => 0.0,
                'variance'      => 0.0,
                'description'   => $cf->keterangan ?? '',
                '_akun_id'      => $cf->akun_id,
            ];
        }

        return $rows;
    }

    public function budgetTotal(MonitoringPeriod $period): float
    {
        return (float) $this->budgetPerAccount($period)->sum();
    }

    /**
     * Total actual for the period across all accounts.
     */
    public function actualTotal(MonitoringPeriod $period): float
    {
        return (float) $this->actualPerAccount($period)->sum();
    }

    /**
     * Budget, actual, and variance totals for many periods at once.
     *
     * Uses batched queries instead of per-period queries (N+1) so the
     * monitoring index loads quickly even with many periods.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, array{budget: float, actual: float, variance: float}>
     */
    public function totalsForPeriods(Collection $periods): array
    {
        $periods = $periods->values();

        if ($periods->isEmpty()) {
            return [];
        }

        $firstIds = $this->firstPeriodIdsOfMonth($periods);

        $budgetRows = $this->bulkBudgetTotals($periods, $firstIds);
        $actualRows = $this->bulkActualTotals($periods);
        $inRows = $this->bulkActualInTotals($periods);

        $result = [];

        foreach ($periods as $period) {
            $budget = (float) ($budgetRows[$period->id] ?? 0);
            $actual = (float) ($actualRows[$period->id] ?? 0);
            $result[$period->id] = [
                'budget' => $budget,
                'actual_in' => (float) ($inRows[$period->id] ?? 0),
                'actual' => $actual,
                'variance' => $budget - $actual,
            ];
        }

        return $result;
    }

    /**
     * Period ids that are the first period of the month for their scope.
     *
     * Legacy budget plan items without a date range are only counted in the
     * first period of each month to avoid double counting.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, int>
     */
    protected function firstPeriodIdsOfMonth(Collection $periods): array
    {
        $firstIds = [];

        $groups = $periods->groupBy(function (MonitoringPeriod $period) {
            return ($period->project_id ?? 'global') . '|' . $period->tanggal_mulai->format('Y-m');
        });

        foreach ($groups as $key => $group) {
            [$projectScope, $yearMonth] = explode('|', $key);
            [$year, $month] = array_map('intval', explode('-', $yearMonth));

            $query = MonitoringPeriod::query()
                ->whereYear('tanggal_mulai', $year)
                ->whereMonth('tanggal_mulai', $month);

            if ($projectScope === 'global') {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', (int) $projectScope);
            }

            $earliestStart = $query->min('tanggal_mulai');

            if ($earliestStart === null) {
                continue;
            }

            $earliest = Carbon::parse($earliestStart);

            foreach ($group as $period) {
                if ($period->tanggal_mulai->format('Y-m-d') === $earliest->format('Y-m-d')) {
                    $firstIds[] = (int) $period->id;
                }
            }
        }

        return $firstIds;
    }

    /**
     * Sum receivable settlements (cash in) across many periods with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, float>
     */
    protected function bulkActualInTotals(Collection $periods): array
    {
        $rows = Payment::query()
            ->join('receivables as r', 'r.id', '=', 'payments.receivable_id')
            ->join('monitoring_periods as mp', function ($join) {
                $join->on(function ($query) {
                    $query->whereColumn('payments.tanggal', '>=', 'mp.tanggal_mulai')
                        ->whereColumn('payments.tanggal', '<=', 'mp.tanggal_selesai')
                        ->where(function ($scope) {
                            $scope->whereNull('mp.project_id')
                                ->orWhereColumn('r.project_id', '=', 'mp.project_id')
                                ->orWhereNull('r.project_id');
                        });
                });
            })
            ->where('payments.jenis', PaymentJenis::Masuk)
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->selectRaw('mp.id AS period_id, COALESCE(SUM(payments.nominal), 0) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->mapWithKeys(fn($total, $periodId) => [(int) $periodId => (float) $total])
            ->all();

        return $rows;
    }

    /**
     * Sum realisasi across many periods with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @return array<int, float>
     */
    protected function bulkActualTotals(Collection $periods): array
    {
        $rows = Realisasi::query()
            ->join('monitoring_periods as mp', function ($join) {
                $join->on(function ($query) {
                    $query->whereColumn('realisasi.tanggal', '>=', 'mp.tanggal_mulai')
                        ->whereColumn('realisasi.tanggal', '<=', 'mp.tanggal_selesai')
                        ->where(function ($scope) {
                            $scope->whereNull('mp.project_id')
                                ->orWhereColumn('realisasi.project_id', '=', 'mp.project_id')
                                ->orWhereNull('realisasi.project_id');
                        });
                });
            })
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->selectRaw('mp.id AS period_id, SUM(realisasi.nominal) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->all();

        return array_map('floatval', $rows);
    }

    /**
     * Budget plan item totals per period with one query.
     *
     * @param  Collection<int, MonitoringPeriod>  $periods
     * @param  array<int, int>  $firstIds
     * @return array<int, float>
     */
    protected function bulkBudgetTotals(Collection $periods, array $firstIds): array
    {
        return BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->join('monitoring_periods as mp', function ($join) use ($firstIds) {
                $join->on(function ($query) use ($firstIds) {
                    $query->whereNotNull('budget_plan_items.tanggal_mulai')
                        ->whereNotNull('budget_plan_items.tanggal_selesai')
                        ->whereColumn('budget_plan_items.tanggal_mulai', '<=', 'mp.tanggal_selesai')
                        ->whereColumn('budget_plan_items.tanggal_selesai', '>=', 'mp.tanggal_mulai');

                    if ($firstIds !== []) {
                        $query->orWhere(function ($legacy) use ($firstIds) {
                            $legacy->whereIn('mp.id', $firstIds)
                                ->where(function ($nullRange) {
                                    $nullRange->whereNull('budget_plan_items.tanggal_mulai')
                                        ->orWhereNull('budget_plan_items.tanggal_selesai');
                                });
                        });
                    }
                });
            })
            ->whereIn('mp.id', $periods->pluck('id')->all())
            ->where(function ($scope) {
                $scope->whereNull('mp.project_id')
                    ->orWhereColumn('budget_plans.project_id', '=', 'mp.project_id');
            })
            ->selectRaw('mp.id AS period_id, SUM(budget_plan_items.nominal) AS total')
            ->groupBy('mp.id')
            ->pluck('total', 'period_id')
            ->mapWithKeys(fn($total, $periodId) => [(int) $periodId => (float) $total])
            ->all();
    }

    public function actualInTotal(MonitoringPeriod $period): float
    {
        // 1. Pelunasan AR (Payment)
        $arTotal = (float) Payment::query()
            ->join('receivables', 'receivables.id', '=', 'payments.receivable_id')
            ->where('payments.jenis', PaymentJenis::Masuk)
            ->whereDate('payments.tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('payments.tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->when($period->project_id, fn($query) => $query->where(fn($sub) => $sub->where('receivables.project_id', $period->project_id)->orWhereNull('receivables.project_id')))
            ->sum('payments.nominal');

        // 2. Manual Posted Cashflow (Masuk)
        $syncedCashflowIds = Realisasi::where('sumber', Realisasi::SUMBER_MANUAL)
            ->whereNotNull('sumber_id')
            ->pluck('sumber_id')
            ->all();

        $cfInTotal = (float) Cashflow::query()
            ->where('status', KasStatus::Posted)
            ->where('jenis', 'masuk')
            ->whereNull('payment_id')
            ->whereNotIn('id', $syncedCashflowIds)
            ->whereDate('tanggal', '>=', $period->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $period->tanggal_selesai->format('Y-m-d'))
            ->when($period->project_id, fn($query) => $query->where(fn($sub) => $sub->where('project_id', $period->project_id)->orWhereNull('project_id')))
            ->sum('nominal');

        return $arTotal + $cfInTotal;
    }

    /**
     * Actual per account from realisasi and manual cashflow outflows within the period.
     *
     * @return Collection<int, float>
     */
    protected function actualPerAccount(MonitoringPeriod $period): Collection
    {
        $periodStart = $period->tanggal_mulai->format('Y-m-d');
        $periodEnd   = $period->tanggal_selesai->format('Y-m-d');

        // 1. Realisasi (proyek + non-proyek)
        $realisasiQuery = Realisasi::query()
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd)
            ->when($period->project_id, fn($query) => $query->where(fn($sub) => $sub->where('project_id', $period->project_id)->orWhereNull('project_id')))
            ->selectRaw('akun_id, SUM(nominal) as total')
            ->groupBy('akun_id');

        $totals = $realisasiQuery->pluck('total', 'akun_id')->map(fn($v) => (float) $v)->all();

        // 2. Manual Posted Cashflow (Keluar) yang belum disync ke Realisasi
        $syncedCashflowIds = Realisasi::where('sumber', Realisasi::SUMBER_MANUAL)
            ->whereNotNull('sumber_id')
            ->pluck('sumber_id')
            ->all();

        $cashflowTotals = Cashflow::query()
            ->where('status', KasStatus::Posted)
            ->where('jenis', 'keluar')
            ->whereNull('payment_id')
            ->whereNotNull('akun_id')
            ->whereNotIn('id', $syncedCashflowIds)
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd)
            ->when($period->project_id, fn($query) => $query->where(fn($sub) => $sub->where('project_id', $period->project_id)->orWhereNull('project_id')))
            ->selectRaw('akun_id, SUM(nominal) as total')
            ->groupBy('akun_id')
            ->pluck('total', 'akun_id')
            ->all();

        foreach ($cashflowTotals as $akunId => $amount) {
            $totals[$akunId] = ($totals[$akunId] ?? 0.0) + (float) $amount;
        }

        return collect($totals);
    }

    /**
     * Budget per account from budget plan items overlapping the period.
     *
     * @return Collection<int, float>
     */
    protected function budgetPerAccount(MonitoringPeriod $period): Collection
    {
        $includeLegacy = ! $this->hasEarlierPeriodInSameMonth($period);

        $query = BudgetPlanItem::query()
            ->join('budget_plans', 'budget_plans.id', '=', 'budget_plan_items.budget_plan_id')
            ->where(function ($query) use ($period, $includeLegacy) {
                $query->whereNotNull('budget_plan_items.tanggal_mulai')
                    ->whereNotNull('budget_plan_items.tanggal_selesai')
                    ->whereDate('budget_plan_items.tanggal_mulai', '<=', $period->tanggal_selesai->format('Y-m-d'))
                    ->whereDate('budget_plan_items.tanggal_selesai', '>=', $period->tanggal_mulai->format('Y-m-d'));

                if ($includeLegacy) {
                    $query->orWhere(function ($legacy) {
                        $legacy->whereNull('budget_plan_items.tanggal_mulai')
                            ->orWhereNull('budget_plan_items.tanggal_selesai');
                    });
                }
            })
            ->when($period->project_id, fn($query) => $query->where('budget_plans.project_id', $period->project_id))
            ->selectRaw('budget_plan_items.akun_id, SUM(budget_plan_items.nominal) as total')
            ->groupBy('budget_plan_items.akun_id');

        return collect($query->pluck('total', 'akun_id')->all());
    }

    /**
     * Whether another monitoring period for the same scope starts earlier in the same month.
     */
    protected function hasEarlierPeriodInSameMonth(MonitoringPeriod $period): bool
    {
        return MonitoringPeriod::query()
            ->where('project_id', $period->project_id)
            ->whereYear('tanggal_mulai', $period->tanggal_mulai->year)
            ->whereMonth('tanggal_mulai', $period->tanggal_mulai->month)
            ->whereDate('tanggal_mulai', '<', $period->tanggal_mulai->format('Y-m-d'))
            ->exists();
    }

    /**
     * Summary rows for the export (per period), filtered by project and search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summaryRows(?int $projectId = null, string $search = ''): array
    {
        $periods = MonitoringPeriod::query()
            ->with('project')
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($search !== '', fn($query) => $query->where('nomor', 'like', '%' . $search . '%'))
            ->orderByDesc('tanggal_mulai')
            ->get();

        $totals = $this->totalsForPeriods($periods);

        return $periods->map(fn(MonitoringPeriod $period) => [
            'nomor' => $period->nomor,
            'periode' => $period->periode_label,
            'week' => $period->week,
            'month' => $period->month,
            'budget' => (float) ($totals[$period->id]['budget'] ?? 0),
            'actual_in' => (float) ($totals[$period->id]['actual_in'] ?? 0),
            'actual_out' => (float) ($totals[$period->id]['actual'] ?? 0),
            'variance' => (float) ($totals[$period->id]['variance'] ?? 0),
        ])->all();
    }

    protected function nextNomor(): string
    {
        do {
            $next = NumberSequence::next('monitoring_period');
            $nomor = 'MON-' . now()->format('Y') . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        } while (MonitoringPeriod::where('nomor', $nomor)->exists());

        return $nomor;
    }
}
