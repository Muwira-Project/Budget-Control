<?php

namespace App\Imports;

use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\Receivable;
use Illuminate\Support\Collection;

/**
 * Import Receivables (AR) from Excel/CSV.
 * Validates duplicate invoice numbers and party references.
 */
class ReceivableImport extends BaseImport
{
    protected array $expectedHeaders = [
        'project_code',
        'party_type_code',
        'party_name',
        'date',
        'invoice_no',
        'due_date',
        'amount',
        'paid',
        'description',
    ];

    /**
     * Validate a single row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $projectCode = trim((string) $row->get('project_code', ''));
        $partyTypeCode = trim(strtolower((string) $row->get('party_type_code', '')));
        $partyName = trim((string) $row->get('party_name', ''));
        $date = $row->get('date', '');
        $invoiceNo = trim((string) $row->get('invoice_no', ''));
        $dueDate = $row->get('due_date', '');
        $amount = $this->normalizeNominal($row->get('amount', ''));
        $paid = $this->normalizeNominal($row->get('paid', '')) ?? 0;
        $description = trim((string) $row->get('description', ''));

        // Required fields
        if ($projectCode === '') {
            return [false, null, 'Project code is required.'];
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

        // Find project by code
        $project = Project::where('kode', $projectCode)->first();
        if (! $project) {
            return [false, null, "Project with code '{$projectCode}' not found."];
        }

        // Find master type by kode (e.g., vendor, supplier, mandor, investor)
        $masterType = MasterType::where('kode', $partyTypeCode)->first();
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

        // Validate party has AR flag
        if (! $masterItem->flag_ar) {
            return [false, null, "Party '{$partyName}' (type: {$partyTypeCode}) does not allow AR transactions."];
        }

        // Check duplicate invoice within this import
        $importKey = "{$project->id}|{$invoiceNo}";
        if (isset($seenKeys[$importKey])) {
            return [false, null, "Duplicate invoice '{$invoiceNo}' for project '{$projectCode}' in this import."];
        }

        // Check duplicate invoice in database
        $existing = Receivable::where('project_id', $project->id)
            ->where('nomor_invoice', $invoiceNo)
            ->exists();
        if ($existing) {
            return [false, null, "Invoice '{$invoiceNo}' already exists for project '{$projectCode}'."];
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
                'project_id' => $project->id,
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
     * Persist a validated receivable row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        Receivable::create($data);
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
