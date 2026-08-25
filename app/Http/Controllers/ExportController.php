<?php

namespace App\Http\Controllers;

use App\Exports\AkunExport;
use App\Exports\AkunVsRealisasiExport;
use App\Exports\CashflowExport;
use App\Exports\CashflowTemplateExport;
use App\Exports\MonitoringSummaryExport;
use App\Exports\PayableExport;
use App\Exports\RealisasiExport;
use App\Exports\ReceivableExport;
use App\Services\MonitoringPeriodService;
use App\Services\ReceivableService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Download the akun report.
     */
    public function akuns(Request $request): BinaryFileResponse
    {
        return $this->download(new AkunExport($this->filters($request)), 'Account_', $request->query('format', 'xlsx'));
    }

    /**
     * Download the realisasi report.
     */
    public function realisasi(Request $request): BinaryFileResponse
    {
        return $this->download(new RealisasiExport($this->filters($request)), 'Actual_', $request->query('format', 'xlsx'));
    }

    /**
     * Download the akun vs realisasi report.
     */
    public function akunVsRealisasi(Request $request): BinaryFileResponse
    {
        return $this->download(new AkunVsRealisasiExport($this->filters($request)), 'Account_vs_Actual_', $request->query('format', 'xlsx'));
    }

    /**
     * Build the export filters from the request query string.
     *
     * @return array{project_id: int|null, start_date: string|null, end_date: string|null, status: string|null}
     */
    /**
     * Download the monitoring summary report.
     */
    public function monitoringSummary(Request $request): BinaryFileResponse
    {
        $format = in_array($request->query('format'), ['csv', 'pdf'], true) ? $request->query('format') : 'xlsx';
        $projectId = $request->integer('project_id') ?: null;
        $search = (string) $request->query('search', '');

        $export = new MonitoringSummaryExport(
            app(MonitoringPeriodService::class)->summaryRows($projectId, $search),
        );

        $filename = 'Monitoring_Summary_'.now()->format('Ymd').'.'.$format;

        return match ($format) {
            'csv' => app(Excel::class)->download($export, $filename, Excel::CSV),
            'pdf' => app(Excel::class)->download($export, $filename, Excel::DOMPDF),
            default => app(Excel::class)->download($export, $filename),
        };
    }

    /**
     * Download the receivables (AR) report.
     */
    public function receivables(Request $request): BinaryFileResponse
    {
        return $this->download(new ReceivableExport($this->receivableFilters($request)), 'AR_', $request->query('format', 'xlsx'));
    }

    /**
     * Download the payables (AP) report.
     */
    public function payables(Request $request): BinaryFileResponse
    {
        return $this->download(new PayableExport($this->payableFilters($request)), 'AP_', $request->query('format', 'xlsx'));
    }

    /**
     * Download the cashflow (Cash In/Out) report.
     */
    public function cashflows(Request $request): BinaryFileResponse
    {
        $format = in_array($request->query('format'), ['csv', 'pdf'], true) ? $request->query('format') : 'xlsx';
        $export = new CashflowExport([
            'jenis' => $request->query('jenis'),
            'start_date' => $this->validDate($request->query('start_date')),
            'end_date' => $this->validDate($request->query('end_date')),
            'sumber' => $request->query('sumber'),
            'cash_account_id' => $request->integer('cash_account_id') ?: null,
        ]);

        $filename = 'Cashflow_'.now()->format('Ymd').'.'.$format;

        return match ($format) {
            'csv' => app(Excel::class)->download($export, $filename, Excel::CSV),
            'pdf' => app(Excel::class)->download($export, $filename, Excel::DOMPDF),
            default => app(Excel::class)->download($export, $filename),
        };
    }

    /**
     * Build the export filters for receivables from the request query string.
     *
     * @return array<string, mixed>
     */
    private function receivableFilters(Request $request): array
    {
        return [
            'project_id' => $request->integer('project_id') ?: null,
            'status' => in_array($request->query('status'), ['belum_dibayar', 'sebagian', 'lunas'], true) ? $request->query('status') : null,
            'aging' => in_array($request->query('aging'), ['current', '1_30', '31_60', '61_90', 'over_90'], true) ? $request->query('aging') : null,
            'ar_category' => in_array($request->query('ar_category'), ['billed', 'unbilled', 'inprogress'], true) ? $request->query('ar_category') : null,
            'po_number' => $request->query('po_number') ?: null,
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
            'amount_min' => $request->filled('amount_min') ? (float) $request->query('amount_min') : null,
            'amount_max' => $request->filled('amount_max') ? (float) $request->query('amount_max') : null,
        ];
    }

    /**
     * Build the export filters for payables from the request query string.
     *
     * @return array<string, mixed>
     */
    private function payableFilters(Request $request): array
    {
        return [
            'project_id' => $request->integer('project_id') ?: null,
            'status' => in_array($request->query('status'), ['belum_bayar', 'sebagian', 'lunas'], true) ? $request->query('status') : null,
            'aging' => in_array($request->query('aging'), ['current', '1_30', '31_60', '61_90', 'over_90'], true) ? $request->query('aging') : null,
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
            'amount_min' => $request->filled('amount_min') ? (float) $request->query('amount_min') : null,
            'amount_max' => $request->filled('amount_max') ? (float) $request->query('amount_max') : null,
        ];
    }

    private function filters(Request $request): array
    {
        return [
            'project_id' => $request->integer('project_id') ?: null,
            'start_date' => $this->validDate($request->query('start_date')),
            'end_date' => $this->validDate($request->query('end_date')),
            'status' => in_array($request->query('status'), ['progress', 'done', 'cancelled'], true) ? $request->query('status') : null,
        ];
    }

    /**
     * Return the date when it is valid, otherwise null.
     */
    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Carbon::hasFormat($value, 'Y-m-d') ? $value : null;
    }

    /**
     * Build the download response for the given export and format.
     */
    private function download(mixed $export, string $baseName, string $format): BinaryFileResponse
    {
        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
        $filename = $baseName.now()->format('Ymd').'.'.$extension;

        return $format === 'pdf'
            ? app(Excel::class)->download($export, $filename, Excel::DOMPDF)
            : app(Excel::class)->download($export, $filename);
    }
}
