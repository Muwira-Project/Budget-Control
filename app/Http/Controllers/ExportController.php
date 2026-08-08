<?php

namespace App\Http\Controllers;

use App\Exports\AkunExport;
use App\Exports\AkunVsRealisasiExport;
use App\Exports\RealisasiExport;
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
    private function filters(Request $request): array
    {
        return [
            'project_id' => $request->integer('project_id') ?: null,
            'start_date' => $this->validDate($request->query('start_date')),
            'end_date' => $this->validDate($request->query('end_date')),
            'status' => in_array($request->query('status'), ['active', 'completed'], true) ? $request->query('status') : null,
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
