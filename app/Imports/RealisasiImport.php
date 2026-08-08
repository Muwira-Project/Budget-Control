<?php

namespace App\Imports;

use App\Models\Akun;
use App\Models\Investor;
use App\Models\Kategori;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RealisasiImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the realisasi import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['project_code', 'account_code', 'date', 'vendor', 'supplier', 'mandor', 'investor', 'amount', 'description', 'category'];

    /**
     * Validate a single realisasi row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $kodeAkun = trim((string) $row->get('account_code'));
        $kodeProject = trim((string) ($row->get('project_code') ?? ''));
        $vendorName = trim((string) ($row->get('vendor') ?? ''));
        $supplierName = trim((string) ($row->get('supplier') ?? ''));
        $mandorName = trim((string) ($row->get('mandor') ?? ''));
        $investorName = trim((string) ($row->get('investor') ?? ''));
        $keterangan = trim((string) ($row->get('description') ?? ''));
        $kategoriName = trim((string) ($row->get('category') ?? ''));
        $nominal = $this->normalizeNominal($row->get('amount'));
        $tanggal = $this->parseTanggal($row->get('date'));

        $partyCount = collect([$vendorName, $supplierName, $mandorName, $investorName])
            ->filter(fn ($value) => $value !== '')
            ->count();

        if ($partyCount === 0) {
            return [false, null, 'Fill in exactly one party: Vendor (services), Supplier (goods), Mandor, or Investor'];
        }

        if ($partyCount > 1) {
            return [false, null, 'Only one party is allowed: Vendor, Supplier, Mandor, or Investor'];
        }

        if ($kodeAkun === '') {
            return [false, null, 'Account Code is required'];
        }

        if ($kodeProject === '') {
            return [false, null, 'Project Code is required'];
        }

        $project = Project::where('kode', $kodeProject)->first();

        if (! $project) {
            return [false, null, 'Project Code '.$kodeProject.' not found'];
        }

        if ($tanggal === null) {
            return [false, null, 'Invalid date, use the YYYY-MM-DD format'];
        }

        if ($nominal === null) {
            return [false, null, 'Amount must be a number'];
        }

        if ($nominal < 0) {
            return [false, null, 'Amount cannot be negative'];
        }

        if (mb_strlen($keterangan) > 1000) {
            return [false, null, 'Description may not be greater than 1000 characters'];
        }

        $akun = Akun::where('kode_akun', $kodeAkun)->first();

        if (! $akun) {
            return [false, null, 'Account Code "'.$kodeAkun.'" not found'];
        }

        $vendorId = null;

        if ($vendorName !== '') {
            $vendor = Vendor::where('nama', $vendorName)->first();

            if (! $vendor) {
                return [false, null, 'Vendor "'.$vendorName.'" not found in the vendor master'];
            }

            $vendorId = $vendor->id;
        }

        $supplierId = null;

        if ($supplierName !== '') {
            $supplier = Supplier::where('nama', $supplierName)->first();

            if (! $supplier) {
                return [false, null, 'Supplier "'.$supplierName.'" not found in the supplier master'];
            }

            $supplierId = $supplier->id;
        }

        $mandorId = null;

        if ($mandorName !== '') {
            $mandor = Mandor::where('nama', $mandorName)->first();

            if (! $mandor) {
                return [false, null, 'Mandor "'.$mandorName.'" not found in the mandor master'];
            }

            $mandorId = $mandor->id;
        }

        $investorId = null;

        if ($investorName !== '') {
            $investor = Investor::where('nama', $investorName)->first();

            if (! $investor) {
                return [false, null, 'Investor "'.$investorName.'" not found in the investor master'];
            }

            $investorId = $investor->id;
        }

        $kategoriId = null;

        if ($kategoriName !== '') {
            $kategori = Kategori::where('nama', $kategoriName)->first();

            if (! $kategori) {
                return [false, null, 'Category "'.$kategoriName.'" not found in the category master'];
            }

            $kategoriId = $kategori->id;
        }

        if (ProjectAkun::query()
            ->where('project_id', $project->id)
            ->where('akun_id', $akun->id)
            ->where('status', 'approved')
            ->doesntExist()) {
            return [false, null, 'Account must have an approved allocation in the selected project'];
        }

        $key = implode('|', [$project->id, $akun->id, $vendorId, $supplierId, $mandorId, $investorId, $kategoriId, $tanggal, $nominal, $keterangan]);

        if (isset($seenKeys[$key])) {
            return [false, null, 'Duplicate data in file'];
        }

        if (Realisasi::where('project_id', $project->id)
            ->where('akun_id', $akun->id)
            ->where('vendor_id', $vendorId)
            ->where('supplier_id', $supplierId)
            ->where('mandor_id', $mandorId)
            ->where('investor_id', $investorId)
            ->whereDate('tanggal', $tanggal)
            ->where('nominal', $nominal)
            ->where('keterangan', $keterangan)
            ->where('kategori_id', $kategoriId)
            ->exists()) {
            return [false, null, 'A matching actual record already exists'];
        }

        $seenKeys[$key] = true;

        return [
            true,
            [
                'project_id' => $project->id,
                'akun_id' => $akun->id,
                'vendor_id' => $vendorId,
                'supplier_id' => $supplierId,
                'mandor_id' => $mandorId,
                'investor_id' => $investorId,
                'kategori_id' => $kategoriId,
                'tanggal' => $tanggal,
                'nominal' => $nominal,
                'keterangan' => $keterangan === '' ? null : $keterangan,
            ],
            null,
        ];
    }

    /**
     * Persist a validated realisasi row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        Realisasi::create($data);
    }

    /**
     * Parse the tanggal value from an Excel cell into a Y-m-d string, or null when invalid.
     */
    protected function parseTanggal(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat($format, (string) $value);

            if ($date !== false && $date->format($format) === (string) $value) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
