<?php

namespace App\Imports;

use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Import Payables (AP) from Excel/CSV.
 * Validates duplicate invoice numbers and party references.
 */
class PayableImport extends BaseImport
{
    protected bool $useProjectCode = true;

    public function __construct(bool $useProjectCode = true)
    {
        $this->useProjectCode = $useProjectCode;
        $this->expectedHeaders = $this->buildExpectedHeaders();
    }

    protected function buildExpectedHeaders(): array
    {
        if ($this->useProjectCode) {
            return [
                'project_code',
                'akun_code',
                'party_type_code',
                'party_name',
                'date',
                'invoice_no',
                'due_date',
                'amount',
                'paid',
                'description',
            ];
        }

        return [
            'akun_code',
            'party_type_code',
            'party_name',
            'date',
            'invoice_no',
            'due_date',
            'amount',
            'paid',
            'description',
        ];
    }

    /**
     * Validate a single row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $projectCode = trim((string) $row->get('project_code', ''));
        $akunCode = trim((string) $row->get('akun_code', ''));
        $partyTypeCode = trim(strtolower((string) $row->get('party_type_code', '')));
        $partyName = trim((string) $row->get('party_name', ''));
        $date = $row->get('date', '');
        $invoiceNo = trim((string) $row->get('invoice_no', ''));
        $dueDate = $row->get('due_date', '');
        $amount = $this->normalizeNominal($row->get('amount', ''));
        $paid = $this->normalizeNominal($row->get('paid', '')) ?? 0;
        $description = trim((string) $row->get('description', ''));

        // Required fields
        if ($this->useProjectCode) {
            if ($projectCode === '') {
                return [false, null, 'Project code is required.'];
            }
        }
        if ($akunCode === '') {
            return [false, null, 'Akun code is required.'];
        }
        if ($partyTypeCode === '') {
            return [false, null, 'Party type code is required (vendor/supplier/mandor/investor).'];
        }
        if ($partyName === '') {
            return [false, null, 'Party name is required.'];
        }
        if ($date === '') {
            return [false, null, 'Date is required.'];
        }
        if ($invoiceNo === '') {
            return [false, null, 'Invoice number is required.'];
        }
        if ($amount === null) {
            return [false, null, 'Amount is required and must be numeric.'];
        }

        $project = null;
        if ($this->useProjectCode) {
            // Find project by code
            $project = Project::where('kode', $projectCode)->first();
            if (! $project) {
                return [false, null, "Project with code '{$projectCode}' not found."];
            }
        }

        // Find akun by kode
        $akun = Akun::where('kode_akun', $akunCode)->first();
        if (! $akun) {
            return [false, null, "Akun with code '{$akunCode}' not found."];
        }

        // Find master type by kode (case-insensitive, e.g., vendor/supplier/mandor/investor)
        $masterType = MasterType::whereRaw('LOWER(kode) = ?', [$partyTypeCode])->first();
        if (! $masterType) {
            return [false, null, "Party type '{$partyTypeCode}' not found. Use: vendor, supplier, mandor, investor."];
        }

        // Find master item by type + name
        $masterItem = MasterItem::where('master_type_id', $masterType->id)
            ->where('nama', $partyName)
            ->first();
        if (! $masterItem) {
            return [false, null, "Party '{$partyName}' not found in type '{$partyTypeCode}'."];
        }

        // Validate party has AP flag
        if (! $masterItem->flag_ap) {
            return [false, null, "Party '{$partyName}' (type: {$partyTypeCode}) does not allow AP transactions."];
        }

        // Check duplicate invoice within this import
        $importKey = $this->useProjectCode ? "{$project->id}|{$invoiceNo}" : $invoiceNo;
        if (isset($seenKeys[$importKey])) {
            $msg = $this->useProjectCode
                ? "Duplicate invoice '{$invoiceNo}' for project '{$projectCode}' in this import."
                : "Duplicate invoice '{$invoiceNo}' in this import (global).";
            return [false, null, $msg];
        }

        // Check duplicate invoice in database - skip if exists
        if ($this->useProjectCode) {
            $existing = Payable::where('project_id', $project->id)
                ->where('nomor_invoice', $invoiceNo)
                ->exists();
            if ($existing) {
                return [false, null, "Invoice '{$invoiceNo}' already exists for project '{$projectCode}' - skipped."];
            }
        } else {
            $existing = Payable::where('nomor_invoice', $invoiceNo)->exists();
            if ($existing) {
                return [false, null, "Invoice '{$invoiceNo}' already exists (global unique) - skipped."];
            }
        }

        // Validate dates
        $parsedDate = $this->parseDate($date);
        if (! $parsedDate) {
            return [false, null, "Invalid date format: '{$date}'. Use YYYY-MM-DD."];
        }

        $parsedDueDate = null;
        if ($dueDate !== '') {
            $parsedDueDate = $this->parseDate($dueDate);
            if (! $parsedDueDate) {
                return [false, null, "Invalid due date format: '{$dueDate}'. Use YYYY-MM-DD."];
            }
        }

        // Paid cannot exceed amount
        if ($paid > $amount) {
            return [false, null, "Paid amount ({$paid}) cannot exceed total amount ({$amount})."];
        }

        $seenKeys[$importKey] = true;

        return [
            true,
            [
                'project_id' => $this->useProjectCode ? $project->id : null,
                'akun_id' => $akun->id,
                'pihak_type_id' => $masterType->id,
                'pihak_item_id' => $masterItem->id,
                'tanggal' => $parsedDate,
                'nomor_invoice' => $invoiceNo,
                'jatuh_tempo' => $parsedDueDate,
                'nominal' => $amount,
                'nominal_dibayar' => $paid,
                'keterangan' => $description ?: null,
            ],
            null,
        ];
    }

    /**
     * Persist a validated payable row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        Payable::create($data);
    }

    /**
     * Parse date string to Y-m-d format.
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $str = trim((string) $value);

        // Try common formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $str);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}