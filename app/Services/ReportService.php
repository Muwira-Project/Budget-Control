<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Profit & Loss (accrual basis): contract revenue vs realized cost per project.
     *
     * Revenue is prorated over the project duration (tanggal_mulai -> target_selesai)
     * when a date range is given; without a range, the full contract value is used.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array{revenue: float, cost: float, profit: float, margin: float}}
     */
    public function profitLoss(?string $startDate = null, ?string $endDate = null, ?int $projectId = null): array
    {
        $projects = Project::query()
            ->withSum('realisasi as realisasi_total', 'nominal')
            ->when($projectId, fn ($query) => $query->where('id', $projectId))
            ->where('status', '!=', ProjectStatus::Cancelled->value)
            ->orderBy('kode')
            ->get();

        $rows = $projects->map(function (Project $project) use ($startDate, $endDate) {
            $query = Realisasi::query()->where('project_id', $project->id);
            $cost = (float) $query
                ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('tanggal', '<=', $endDate))
                ->sum('nominal');

            $revenue = $this->proratedRevenue($project, $startDate, $endDate);
            $profit = $revenue - $cost;

            return [
                'project_id' => $project->id,
                'kode' => $project->kode,
                'nama' => $project->nama,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->values()->all();

        $revenue = array_sum(array_column($rows, 'revenue'));
        $cost = array_sum(array_column($rows, 'cost'));
        $profit = $revenue - $cost;

        return [
            'rows' => $rows,
            'totals' => [
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Contract revenue recognized within the reporting period.
     *
     * When both the project duration and the report range are known, revenue is
     * prorated by the number of overlapping days. Otherwise the full contract
     * value (nilai_total) applies, preserving the previous behaviour.
     */
    private function proratedRevenue(Project $project, ?string $startDate, ?string $endDate): float
    {
        $total = (float) $project->nilai_total;

        // No range or no project duration -> full contract value.
        if ($startDate === null || $endDate === null || $project->tanggal_mulai === null || $project->target_selesai === null) {
            return $total;
        }

        $projectStart = $project->tanggal_mulai;
        $projectEnd = $project->target_selesai;

        $durationDays = $projectStart->diffInDays($projectEnd) + 1;

        if ($durationDays <= 0) {
            return $total;
        }

        $rangeStart = Carbon::parse($startDate)->startOfDay();
        $rangeEnd = Carbon::parse($endDate)->startOfDay();

        // Overlap of [projectStart, projectEnd] with [rangeStart, rangeEnd].
        $overlapStart = $projectStart->greaterThan($rangeStart) ? $projectStart : $rangeStart;
        $overlapEnd = $projectEnd->lessThan($rangeEnd) ? $projectEnd : $rangeEnd;

        if ($overlapEnd->lt($overlapStart)) {
            return 0.0;
        }

        $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;

        return round($total * ($overlapDays / $durationDays), 2);
    }

    /**
     * Cash flow per cash account (cash basis). Only posted entries are counted.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array{saldo_awal: float, masuk: float, keluar: float, tr_in: float, tr_out: float, saldo_akhir: float}}
     */
    public function cashFlow(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = CashAccount::query()->orderBy('kode')->get();

        $rows = $accounts->map(function (CashAccount $account) use ($startDate, $endDate) {
            $inBefore = 0.0;
            $outBefore = 0.0;
            $trBefore = ['in' => 0.0, 'out' => 0.0];

            // Saldo sebelum periode (saldo_awal + semua posting sebelum start).
            if ($startDate !== null) {
                $before = Cashflow::query()
                    ->where('status', 'posted')
                    ->where('cash_account_id', $account->id)
                    ->whereDate('tanggal', '<', $startDate);
                $inBefore = (float) (clone $before)->where('jenis', 'masuk')->sum('nominal');
                $outBefore = (float) (clone $before)->where('jenis', 'keluar')->sum('nominal');

                $trBefore = $this->transferTotals($account->id, $startDate, true);
            }

            $saldoAwal = (float) $account->saldo_awal + $inBefore - $outBefore + $trBefore['in'] - $trBefore['out'];

            // Mutasi dalam periode.
            $mutation = Cashflow::query()
                ->where('status', 'posted')
                ->where('cash_account_id', $account->id);
            if ($startDate !== null) {
                $mutation->whereDate('tanggal', '>=', $startDate);
            }
            if ($endDate !== null) {
                $mutation->whereDate('tanggal', '<=', $endDate);
            }
            $masuk = (float) (clone $mutation)->where('jenis', 'masuk')->sum('nominal');
            $keluar = (float) (clone $mutation)->where('jenis', 'keluar')->sum('nominal');

            $trPeriod = $this->transferTotals($account->id, $startDate, false, $endDate);

            return [
                'kode' => $account->kode,
                'nama' => $account->nama,
                'jenis' => $account->jenis->label(),
                'saldo_awal' => $saldoAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'tr_in' => $trPeriod['in'],
                'tr_out' => $trPeriod['out'],
                'saldo_akhir' => $saldoAwal + $masuk - $keluar + $trPeriod['in'] - $trPeriod['out'],
            ];
        })->values()->all();

        $sum = fn (string $key) => array_sum(array_column($rows, $key));

        return [
            'rows' => $rows,
            'totals' => [
                'saldo_awal' => $sum('saldo_awal'),
                'masuk' => $sum('masuk'),
                'keluar' => $sum('keluar'),
                'tr_in' => $sum('tr_in'),
                'tr_out' => $sum('tr_out'),
                'saldo_akhir' => $sum('saldo_akhir'),
            ],
        ];
    }

    /**
     * Aging AR/AP as of a date.
     *
     * @return array{as_of: string, ar_rows: array<int, array<string, mixed>>, ap_rows: array<int, array<string, mixed>>, ar_totals: array<string, float>, ap_totals: array<string, float>}
     */
    public function aging(?string $asOf = null): array
    {
        $asOf = Carbon::parse($asOf ?? today());

        $arRows = Receivable::query()
            ->with('project')
            ->get()
            ->filter(fn (Receivable $receivable) => $receivable->sisa > 0)
            ->map(fn (Receivable $receivable) => [
                'label' => $receivable->project?->kode.' - '.$receivable->project?->nama,
                'tanggal' => $receivable->tanggal->format('d M Y'),
                'jatuh_tempo' => $receivable->jatuh_tempo?->format('d M Y'),
                'sisa' => (float) $receivable->sisa,
                'bucket' => $this->bucket($receivable->jatuh_tempo, $asOf),
            ])
            ->values();

        $apRows = Payable::query()
            ->with(['pihakType', 'pihakItem'])
            ->get()
            ->filter(fn (Payable $payable) => $payable->sisa > 0)
            ->map(fn (Payable $payable) => [
                'label' => $payable->pihak ?: '#'.$payable->id,
                'tanggal' => $payable->tanggal->format('d M Y'),
                'jatuh_tempo' => $payable->jatuh_tempo?->format('d M Y'),
                'sisa' => (float) $payable->sisa,
                'bucket' => $this->bucket($payable->jatuh_tempo, $asOf),
            ])
            ->values();

        $buckets = ['current', '1_30', '31_60', '61_90', 'over_90'];

        return [
            'as_of' => $asOf->format('d M Y'),
            'ar_rows' => $arRows->all(),
            'ap_rows' => $apRows->all(),
            'ar_totals' => $this->bucketTotals($arRows, $buckets),
            'ap_totals' => $this->bucketTotals($apRows, $buckets),
        ];
    }

    /**
     * Aging bucket key for a due date.
     */
    private function bucket(?Carbon $due, Carbon $asOf): string
    {
        if ($due === null || $due->gte($asOf)) {
            return 'current';
        }

        $days = (int) $due->diffInDays($asOf);

        return match (true) {
            $days <= 30 => '1_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => 'over_90',
        };
    }

    /**
     * Per-bucket totals for a collection of rows.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $buckets
     * @return array<string, float>
     */
    private function bucketTotals($rows, array $buckets): array
    {
        $totals = array_fill_keys($buckets, 0.0);

        foreach ($rows as $row) {
            $totals[$row['bucket']] += (float) $row['sisa'];
        }

        return $totals;
    }

    /**
     * Posted transfer totals for an account, optionally limited before/within a period.
     *
     * @return array{in: float, out: float}
     */
    private function transferTotals(int $accountId, ?string $startDate, bool $beforePeriod, ?string $endDate = null): array
    {
        $inQuery = FundTransfer::query()->where('status', 'posted')->where('ke_cash_account_id', $accountId);
        $outQuery = FundTransfer::query()->where('status', 'posted')->where('dari_cash_account_id', $accountId);

        if ($beforePeriod) {
            $inQuery->when($startDate, fn ($q) => $q->whereDate('tanggal', '<', $startDate));
            $outQuery->when($startDate, fn ($q) => $q->whereDate('tanggal', '<', $startDate));
        } else {
            $inQuery
                ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('tanggal', '<=', $endDate));
            $outQuery
                ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('tanggal', '<=', $endDate));
        }

        return [
            'in' => (float) $inQuery->sum('nominal'),
            'out' => (float) $outQuery->sum('nominal'),
        ];
    }
}
