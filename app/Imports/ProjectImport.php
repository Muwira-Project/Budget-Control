<?php

namespace App\Imports;

use App\Models\MasterItem;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Import projects from Excel.
 */
class ProjectImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the project import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['code', 'name', 'location', 'division', 'pic', 'project_category', 'sub_work', 'period', 'type', 'qty', 'unit', 'unit_price', 'tax', 'start_date', 'target_finish', 'status'];

    /**
     * Validate a single project row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $kode = trim((string) $row->get('code'));
        $nama = trim((string) $row->get('name'));
        $lokasi = trim((string) $row->get('location'));
        $division = trim((string) $row->get('division'));
        $pic = trim((string) $row->get('pic'));
        $projectCategory = trim((string) $row->get('project_category'));
        $subWork = trim((string) $row->get('sub_work'));
        $periode = trim((string) $row->get('period'));
        $jenisRaw = trim((string) $row->get('type'));
        $jenis = $this->normalizeJenis($jenisRaw);

        // Optional: default to 'barang' if not provided
        if ($jenis === '') {
            $jenis = 'barang';
        }

        $qty = $this->normalizeNominal($row->get('qty'));
        $satuan = trim((string) $row->get('unit'));
        $hargaSatuan = $this->normalizeNominal($row->get('unit_price'));
        $pajak = $this->normalizeNominal($row->get('tax'));
        $tanggalMulai = trim((string) $row->get('start_date'));
        $targetSelesai = trim((string) $row->get('target_finish'));
        $status = strtolower(trim((string) $row->get('status')));

        // Required fields
        if ($kode === '') {
            return [false, null, 'Code is required'];
        }

        if ($nama === '') {
            return [false, null, 'Name is required'];
        }

        // Validate jenis
        if (! in_array($jenis, ['barang', 'jasa'], true)) {
            return [false, null, 'Type must be "barang" or "jasa"'];
        }

        // Apply default tax based on jenis if not provided
        if ($pajak === null) {
            $pajak = $jenis === 'jasa' ? 2.0 : 11.0;
        }

        // Validate status
        $validStatuses = ['draft', 'progress', 'done', 'cancelled'];
        if ($status === '') {
            $status = 'draft';
        } elseif (! in_array($status, $validStatuses, true)) {
            return [false, null, 'Status must be one of: draft, progress, done, cancelled'];
        }

        // Validate project category if provided
        $projectCategoryId = null;
        if ($projectCategory !== '') {
            $category = MasterItem::whereHas('masterType', fn ($query) => $query->where('kode', 'PROJECT_CATEGORY')->where('aktif', true))
                ->where('aktif', true)
                ->where('nama', $projectCategory)
                ->first();

            if (! $category) {
                return [false, null, 'Project Category "'.$projectCategory.'" not found'];
            }

            $projectCategoryId = $category->id;
        }

        // Validate division if provided
        $divisionId = null;
        if ($division !== '') {
            $divisionItem = MasterItem::whereHas('masterType', fn ($query) => $query->where('kode', 'DIVISION')->where('aktif', true))
                ->where('aktif', true)
                ->where(function ($query) use ($division) {
                    $query->where('kode', $division)->orWhere('nama', $division);
                })
                ->first();

            if (! $divisionItem) {
                return [false, null, 'Division "'.$division.'" not found in Master Division'];
            }

            $divisionId = $divisionItem->id;
        }

        // Validate PIC if provided
        $picId = null;
        if ($pic !== '') {
            $picItem = MasterItem::whereHas('masterType', fn ($query) => $query->where('kode', 'PIC')->where('aktif', true))
                ->where('aktif', true)
                ->where(function ($query) use ($pic) {
                    $query->where('kode', $pic)->orWhere('nama', $pic);
                })
                ->first();

            if (! $picItem) {
                return [false, null, 'PIC "'.$pic.'" not found in Master PIC'];
            }

            $picId = $picItem->id;
        }

        // Check duplicate in file
        if (isset($seenKeys[$kode])) {
            return [false, null, 'Code '.$kode.' is duplicated in the file'];
        }

        $seenKeys[$kode] = true;

        // Check if project exists in database (for upsert)
        $existingProject = Project::where('kode', $kode)->first();
        $isUpdate = $existingProject !== null;

        return [
            true,
            [
                'kode' => $kode,
                'nama' => $nama,
                'lokasi' => $lokasi ?: null,
                'division_id' => $divisionId,
                'pic_id' => $picId,
                'project_category_id' => $projectCategoryId,
                'sub_work' => $subWork ?: null,
                'periode' => $periode ?: null,
                'jenis' => $jenis,
                'qty' => $qty,
                'satuan' => $satuan ?: null,
                'harga_satuan' => $hargaSatuan,
                'pajak' => $pajak ?? 0,
                'tanggal_mulai' => $tanggalMulai !== '' ? $tanggalMulai : null,
                'target_selesai' => $targetSelesai !== '' ? $targetSelesai : null,
                'status' => $status,
                '_is_update' => $isUpdate,
                '_existing_id' => $existingProject?->id,
            ],
            null,
        ];
    }

    /**
     * Persist a validated project row (create or update).
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        $isUpdate = $data['_is_update'] ?? false;
        $existingId = $data['_existing_id'] ?? null;

        unset($data['_is_update'], $data['_existing_id']);

        if ($isUpdate && $existingId) {
            Project::where('id', $existingId)->update($data);
        } else {
            Project::create($data);
        }
    }

    /**
     * Extract 'barang' or 'jasa' from a string like "jasa - konstruksi" or "barang Pasir".
     * Returns empty string if neither is found at the start.
     */
    protected function normalizeJenis(string $input): string
    {
        $normalized = strtolower(trim($input));

        // Match 'barang' or 'jasa' at the start, optionally followed by separator (-, :, space)
        if (preg_match('/^(barang|jasa)\b/', $normalized, $matches)) {
            return $matches[1];
        }

        return '';
    }
}