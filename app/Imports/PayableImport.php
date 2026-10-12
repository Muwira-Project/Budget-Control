<?php

namespace App\Imports;

use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use Illuminate\Support\Carbon;
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
                'cash_account_code',
                'cashflow_ref',
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
            'cash_account_code',
            'cashflow_ref',
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
        $cashAccountCode = trim((string) $row->get('cash_account_code', ''));
        $cashflowRef = trim((string) $row->get('cashflow_ref', ''));
        $jenisPajak = trim((string) $row->get('tax_type', '')) ?: null;
        $discountPercent = $this->normalizeNominal($row->get('discount_percent', '')) ?? 0;

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
        $importKey = $invoiceNo;
        if (isset($seenKeys[$importKey])) {
            return [false, null, "Duplicate invoice '{$invoiceNo}' in this import (global)."];
        }

        // The database enforces invoice numbers as globally unique in both modes.
        if (Payable::where('nomor_invoice', $invoiceNo)->exists()) {
            return [false, null, "Invoice '{$invoiceNo}' already exists (global unique) - skipped."];
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
                'jenis_pajak' => $jenisPajak,
                'discount_percent' => $discountPercent,
                'keterangan' => $description ?: null,
                '_cash_account_code' => $cashAccountCode ?: null,
                '_cashflow_ref' => $cashflowRef ?: null,
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
        $cashAccountCode = $data['_cash_account_code'] ?? null;
        $cashflowRef = $data['_cashflow_ref'] ?? null;

        $cashAccount = null;
        if ($cashAccountCode !== null && $cashAccountCode !== '') {
            $cashAccount = CashAccount::where('kode', $cashAccountCode)->first();
        }

        if (! $cashAccount) {
            $cashAccount = CashAccount::where('status', 'active')->first();
        }

        if ($cashAccount) {
            $data['cash_account_id'] = $cashAccount->id;
        }

        if ($cashflowRef !== null && $cashflowRef !== '') {
            $data['cashflow_ref'] = $cashflowRef;
        }

        unset($data['_cash_account_code'], $data['_cashflow_ref']);

        $payable = Payable::create($data);

        // For non-project imports, create a linked Cashflow (Cash Out / pengeluaran_lain)
        if (! $this->useProjectCode && $cashAccount) {
            $this->createLinkedCashflow($payable, $cashAccount);
        }
    }

    /**
     * Create a linked Cashflow entry for the payable (non-project only).
     */
    private function createLinkedCashflow(Payable $payable, CashAccount $cashAccount): void
    {
        Cashflow::create([
            'tanggal'         => $payable->tanggal,
            'jenis'           => \App\Enums\CashflowJenis::Keluar,
            'sumber'          => 'pengeluaran_lain',
            'cash_account_id' => $cashAccount->id,
            'akun_id'         => $payable->akun_id,
            'project_id'      => null,
            'pihak_type_id'   => $payable->pihak_type_id,
            'pihak_item_id'   => $payable->pihak_item_id,
            'payable_id'      => $payable->id,
            'nominal'         => $payable->nominal,
            'keterangan'      => $payable->keterangan ?? 'Import AP: '.$payable->nomor_invoice,
            'status'          => \App\Enums\KasStatus::Posted,
            'created_by'      => auth()->id() ?? 1,
            'posted_by'       => auth()->id() ?? 1,
            'posted_at'       => now(),
        ]);
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
