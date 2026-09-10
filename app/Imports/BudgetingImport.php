<?php

namespace App\Imports;

use App\Enums\AllocationStatus;
use App\Enums\JenisAkun;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Support\Collection;

class BudgetingImport extends BaseImport
{
    /**
     * Modernized column headers for budgeting import.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = [
        'project_code',
        'project_name',
        'budgeting_number',
        'account_code',
        'account_name',
        'type',
        'party_type',
        'party_name',
        'budget',
        'allocation',
        'status',
    ];

    /**
     * Legacy headers (8 columns) for backward compatibility.
     *
     * @var array<int, string>
     */
    protected array $legacyHeaders = [
        'project_code',
        'account_code',
        'type',
        'party_type',
        'party_name',
        'budget',
        'allocation',
        'status',
    ];

    /**
     * Read and validate the imported rows, supporting both modern and legacy headers.
     */
    public function collection(Collection $rows): void
    {
        $dataRows = $rows->filter(fn (Collection $row) => $row->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty());

        if ($dataRows->isEmpty()) {
            $this->fatalError = 'The file contains no data.';

            return;
        }

        $actualHeaders = $rows->first()->keys()->all();
        $normalizedActual = array_map('strtolower', $actualHeaders);
        $normalizedLegacy = array_map('strtolower', $this->legacyHeaders);

        // Dynamically switch expected headers if user uploaded the legacy 8-column template
        if ($normalizedActual === $normalizedLegacy) {
            $this->expectedHeaders = $this->legacyHeaders;
        }

        parent::collection($rows);
    }

    /**
     * Validate a single budgeting row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        // Support both standard English slugified keys and Indonesian aliases
        $projectCode = trim((string) ($row->get('project_code') ?? $row->get('kode_proyek') ?? ''));
        $akunCode = trim((string) ($row->get('account_code') ?? $row->get('kode_akun') ?? ''));
        $rawType = strtolower(trim((string) ($row->get('type') ?? $row->get('tipe') ?? $row->get('jenis') ?? '')));
        $partyTypeCode = strtolower(trim((string) ($row->get('party_type') ?? $row->get('tipe_pihak') ?? $row->get('jenis_pihak') ?? '')));
        $partyName = trim((string) ($row->get('party_name') ?? $row->get('nama_pihak') ?? $row->get('deskripsi') ?? $row->get('keterangan') ?? ''));
        $budget = $this->normalizeNominal($row->get('budget') ?? $row->get('anggaran') ?? '');
        $allocation = $this->normalizeNominal($row->get('allocation') ?? $row->get('alokasi') ?? '');
        $rawStatus = strtolower(trim((string) ($row->get('status') ?? '')));

        if ($akunCode === '') {
            return [false, null, 'Account Code is required.'];
        }

        $akun = Akun::where('kode_akun', $akunCode)
            ->orWhereRaw('LOWER(TRIM(kode_akun)) = ?', [strtolower($akunCode)])
            ->first();

        if (! $akun) {
            return [false, null, "Account Code '{$akunCode}' not found in Chart of Accounts."];
        }

        if ($budget === null && $allocation === null) {
            return [false, null, 'Budget or Allocation amount is required and must be numeric.'];
        }

        $budget = $budget ?? $allocation;
        $allocation = $allocation ?? $budget;

        $status = match ($rawStatus) {
            'draft' => AllocationStatus::Draft,
            'waiting' => AllocationStatus::Waiting,
            'rejected' => AllocationStatus::Rejected,
            default => AllocationStatus::Approved,
        };

        // Comprehensive Non-Project keyword recognition
        $normalizedProject = strtolower($projectCode);
        $isNonProject = in_array($normalizedProject, [
            '',
            '-',
            '--',
            'none',
            'null',
            'non-project',
            'non project',
            'non_project',
            'nonproject',
            'non-proyek',
            'non proyek',
            'non_proyek',
            'nonproyek',
            'n/a',
            'na',
        ], true);

        $isProject = ! $isNonProject;
        $projectId = null;

        $defaultType = ($akun->jenis_akun === JenisAkun::Pendapatan || $akun->jenis_akun?->value === 'pendapatan')
            ? 'other_income'
            : 'other_outcome';

        $type = match ($rawType) {
            'ap', 'hutang', 'payable' => 'ap',
            'ar', 'piutang', 'receivable' => 'ar',
            'other_income', 'income', 'pendapatan', 'pemasukan' => 'other_income',
            'other_outcome', 'outcome', 'expense', 'pengeluaran', 'biaya' => 'other_outcome',
            default => $defaultType,
        };

        $pihakTypeId = null;
        $pihakItemId = null;
        $customName = null;

        if ($isProject) {
            $project = Project::where('kode', $projectCode)
                ->orWhereRaw('LOWER(TRIM(kode)) = ?', [strtolower($projectCode)])
                ->first();

            if (! $project) {
                return [false, null, "Project Code '{$projectCode}' not found in Projects."];
            }

            $projectId = $project->id;
            $dedupKey = "proj-{$projectId}-{$akun->id}";

            if (isset($seenKeys[$dedupKey])) {
                return [false, null, "Duplicate row in file for Project '{$projectCode}' and Account '{$akunCode}'."];
            }
            $seenKeys[$dedupKey] = true;
        } else {
            // Non-Project Budget (Overhead, Operational, AP, AR)
            if (in_array($type, ['ap', 'ar'], true)) {
                if ($partyTypeCode !== '') {
                    $masterType = MasterType::whereRaw('LOWER(TRIM(kode)) = ?', [$partyTypeCode])
                        ->orWhereRaw('LOWER(TRIM(nama)) = ?', [$partyTypeCode])
                        ->first();

                    if (! $masterType) {
                        return [false, null, "Party type '{$partyTypeCode}' not found in Master Types."];
                    }
                    $pihakTypeId = $masterType->id;

                    if ($partyName !== '') {
                        $masterItem = MasterItem::where('master_type_id', $masterType->id)
                            ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower($partyName)])
                            ->first();

                        if (! $masterItem) {
                            $masterItem = MasterItem::create([
                                'master_type_id' => $masterType->id,
                                'kode' => 'MI-'.strtoupper(substr(uniqid(), -6)),
                                'nama' => $partyName,
                                'aktif' => true,
                                'flag_ap' => ($type === 'ap'),
                                'flag_ar' => ($type === 'ar'),
                            ]);
                        }
                        $pihakItemId = $masterItem->id;
                    } else {
                        $customName = 'Non-Project - ' . ($akun->nama_akun ?? 'Allocation');
                    }
                } elseif ($partyName !== '') {
                    $customName = $partyName;
                } else {
                    $customName = 'Non-Project - ' . ($akun->nama_akun ?? 'Allocation');
                }
            } else {
                $customName = $partyName !== '' ? $partyName : ('Non-Project - ' . ($akun->nama_akun ?? 'Allocation'));
            }

            $identifier = $pihakItemId ? "item-{$pihakItemId}" : 'desc-'.md5(strtolower(trim($customName ?? '')));
            $dedupKey = "nonproj-{$akun->id}-{$type}-{$identifier}";

            if (isset($seenKeys[$dedupKey])) {
                return [false, null, "Duplicate non-project allocation row in file for Account '{$akunCode}' with description '{$customName}'."];
            }
            $seenKeys[$dedupKey] = true;
        }

        $currentUserId = auth()->id() ?? User::first()?->id;

        return [
            true,
            [
                'project_id' => $projectId,
                'akun_id' => $akun->id,
                'type' => $type,
                'pihak_type_id' => $pihakTypeId,
                'pihak_item_id' => $pihakItemId,
                'custom_name' => $customName,
                'budget' => $budget,
                'allocation' => $allocation,
                'status' => $status,
                'created_by' => $currentUserId,
                'approved_by' => $status === AllocationStatus::Approved ? $currentUserId : null,
                'approved_at' => $status === AllocationStatus::Approved ? now() : null,
            ],
            null,
        ];
    }

    /**
     * Persist a validated budgeting row.
     *
     * @param array<string, mixed> $data
     */
    protected function persist(array $data): void
    {
        if ($data['project_id'] !== null) {
            $existing = ProjectAkun::where('project_id', $data['project_id'])
                ->where('akun_id', $data['akun_id'])
                ->first();

            if ($existing) {
                $existing->update([
                    'budget' => $data['budget'],
                    'allocation' => $data['allocation'],
                    'status' => $data['status'],
                    'approved_by' => $data['approved_by'] ?? $existing->approved_by,
                    'approved_at' => $data['approved_at'] ?? $existing->approved_at,
                ]);

                return;
            }
        } else {
            $query = ProjectAkun::whereNull('project_id')
                ->where('akun_id', $data['akun_id'])
                ->where('type', $data['type']);

            if ($data['pihak_item_id'] !== null) {
                $query->where('pihak_item_id', $data['pihak_item_id']);
            } elseif ($data['custom_name'] !== null) {
                $query->where('custom_name', $data['custom_name']);
            }

            $existing = $query->first();
            if ($existing) {
                $existing->update([
                    'budget' => $data['budget'],
                    'allocation' => $data['allocation'],
                    'status' => $data['status'],
                    'approved_by' => $data['approved_by'] ?? $existing->approved_by,
                    'approved_at' => $data['approved_at'] ?? $existing->approved_at,
                ]);

                return;
            }
        }

        ProjectAkun::create($data);
    }
}
