<?php

namespace App\Imports;

use App\Enums\CashflowJenis;
use App\Enums\KasStatus;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Import Cash In/Out (Cashflow) records from Excel.
 */
class CashflowImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the cashflow import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['date', 'type', 'source', 'cash_account', 'account_coa', 'project', 'party_type', 'party', 'amount', 'description'];

    /**
     * Validate a single cashflow row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $date = trim((string) $row->get('date'));
        $type = strtolower(trim((string) $row->get('type')));
        $source = strtolower(trim((string) $row->get('source')));
        $cashAccountCode = trim((string) $row->get('cash_account'));
        $akunCode = trim((string) $row->get('account_coa'));
        $projectCode = trim((string) $row->get('project'));
        $partyTypeCode = trim((string) $row->get('party_type'));
        $partyCode = trim((string) $row->get('party'));
        $nominal = $this->normalizeNominal($row->get('amount'));
        $keterangan = trim((string) $row->get('description'));

        // Required fields
        if ($date === '') {
            return [false, null, 'Date is required'];
        }

        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            return [false, null, 'Date must be in YYYY-MM-DD format'];
        }

        if ($type === '' || ! in_array($type, ['masuk', 'keluar'], true)) {
            return [false, null, 'Type must be "masuk" (Cash In) or "keluar" (Cash Out)'];
        }

        if ($source === '') {
            return [false, null, 'Source is required'];
        }

        if (! in_array($source, ['pendapatan', 'pelunasan_ar', 'pelunasan_ap', 'pengeluaran_lain'], true)) {
            return [false, null, 'Source must be "pendapatan", "pelunasan_ar", "pelunasan_ap", or "pengeluaran_lain"'];
        }

        if ($akunCode === '') {
            return [false, null, 'Account (COA) is required'];
        }

        if ($nominal === null || $nominal <= 0) {
            return [false, null, 'Amount must be a positive number'];
        }

        // Validate Cash Account
        $cashAccountId = null;
        if ($cashAccountCode !== '') {
            $cashAccount = CashAccount::where('kode', $cashAccountCode)->where('status', 'active')->first();
            if (! $cashAccount) {
                return [false, null, 'Cash Account "'.$cashAccountCode.'" not found or inactive'];
            }
            $cashAccountId = $cashAccount->id;
        }

        // Validate Akun (COA)
        $akun = Akun::where('kode_akun', $akunCode)->first();
        if (! $akun) {
            return [false, null, 'Account "'.$akunCode.'" not found in Master Akun'];
        }

        // Validate Project (optional)
        $projectId = null;
        if ($projectCode !== '') {
            $project = Project::where('kode', $projectCode)->first();
            if (! $project) {
                return [false, null, 'Project "'.$projectCode.'" not found'];
            }
            $projectId = $project->id;
        }

        // Validate Party Type (optional)
        $pihakTypeId = null;
        $pihakItemId = null;
        if ($partyTypeCode !== '' && $partyCode !== '') {
            $partyType = MasterType::where('kode', $partyTypeCode)->first();
            if (! $partyType) {
                return [false, null, 'Party Type "'.$partyTypeCode.'" not found'];
            }
            $pihakTypeId = $partyType->id;

            $partyItem = MasterItem::where('master_type_id', $pihakTypeId)->where('kode', $partyCode)->first();
            if (! $partyItem) {
                return [false, null, 'Party "'.$partyCode.'" not found in '.ucfirst($partyTypeCode)];
            }
            $pihakItemId = $partyItem->id;
        }

        // Check duplicate in file (by date + type + source + akun + nominal)
        $key = $date.'-'.$type.'-'.$source.'-'.$akunCode.'-'.(string) $nominal;
        if (isset($seenKeys[$key])) {
            return [false, null, 'Duplicate entry: same date/type/source/account/amount already in the file'];
        }
        $seenKeys[$key] = true;

        return [
            true,
            [
                'tanggal' => $date,
                'jenis' => $type === 'masuk' ? CashflowJenis::Masuk : CashflowJenis::Keluar,
                'sumber' => $source,
                'cash_account_id' => $cashAccountId,
                'akun_id' => $akun->id,
                'project_id' => $projectId,
                'pihak_type_id' => $pihakTypeId,
                'pihak_item_id' => $pihakItemId,
                'nominal' => $nominal,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'status' => KasStatus::Draft,
                'created_by' => auth()->id(),
            ],
            null,
        ];
    }

    /**
     * Persist a validated cashflow row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        Cashflow::create($data);
    }
}
