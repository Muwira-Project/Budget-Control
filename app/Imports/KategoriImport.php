<?php

namespace App\Imports;

use App\Models\Kategori;
use Illuminate\Support\Collection;

class KategoriImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the kategori import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['code', 'name'];

    /**
     * Validate a single kategori row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $kode = trim((string) $row->get('code'));
        $nama = trim((string) $row->get('name'));

        if ($kode === '') {
            return [false, null, 'Code is required'];
        }

        if ($nama === '') {
            return [false, null, 'Name is required'];
        }

        if (isset($seenKeys[$kode])) {
            return [false, null, 'Code '.$kode.' is duplicated in the file'];
        }

        $seenKeys[$kode] = true;

        $existing = Kategori::where('kode', $kode)->first();
        $isUpdate = $existing !== null;

        return [
            true,
            [
                'kode' => $kode,
                'nama' => $nama,
                '_is_update' => $isUpdate,
                '_existing_id' => $existing?->id,
            ],
            null,
        ];
    }

    /**
     * Persist a validated kategori row (upsert).
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        $isUpdate = $data['_is_update'] ?? false;
        $existingId = $data['_existing_id'] ?? null;

        unset($data['_is_update'], $data['_existing_id']);

        if ($isUpdate && $existingId) {
            Kategori::where('id', $existingId)->update($data);
        } else {
            Kategori::create($data);
        }
    }
}
