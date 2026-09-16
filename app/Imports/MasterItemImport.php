<?php

namespace App\Imports;

use App\Models\MasterItem;
use App\Models\MasterType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MasterItemImport extends BaseImport
{
    public function __construct(public MasterType $masterType)
    {
        $this->masterType->load('fields');

        $headers = ['code', 'name'];

        foreach ($this->masterType->fields as $field) {
            $headers[] = Str::slug($field->label, '_');
        }

        if ($this->masterType->flag_ar) {
            $headers[] = 'ar';
        }

        if ($this->masterType->flag_ap) {
            $headers[] = 'ap';
        }

        $headers[] = 'status';

        $this->expectedHeaders = $headers;
    }

    /**
     * Validate a single master item row.
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

        if (strlen($kode) > 50) {
            return [false, null, 'Code must not exceed 50 characters'];
        }

        if ($nama === '') {
            return [false, null, 'Name is required'];
        }

        if (isset($seenKeys[$kode])) {
            return [false, null, 'Code '.$kode.' is duplicated in the file'];
        }

        $data = [];
        foreach ($this->masterType->fields as $field) {
            $slug = Str::slug($field->label, '_');
            $rawVal = $row->get($slug);

            $val = match ($field->tipe) {
                'number' => $this->normalizeNominal($rawVal),
                'date' => $this->normalizeDate($rawVal),
                default => $rawVal === null || trim((string) $rawVal) === '' ? null : trim((string) $rawVal),
            };

            if ($field->is_required && ($val === null || $val === '')) {
                return [false, null, "Field '{$field->label}' is required"];
            }

            if ($field->tipe === 'number' && $rawVal !== null && trim((string) $rawVal) !== '' && $val === null) {
                return [false, null, "Field '{$field->label}' must be a valid number"];
            }

            $data[(string) $field->id] = $val;
        }

        $flagAr = null;
        if ($this->masterType->flag_ar) {
            $rawAr = strtolower(trim((string) ($row->get('ar') ?? '')));
            $flagAr = in_array($rawAr, ['yes', 'ya', '1', 'true', 'y'], true) ? true : (in_array($rawAr, ['no', 'tidak', '0', 'false', 'n'], true) ? false : null);
        }

        $flagAp = null;
        if ($this->masterType->flag_ap) {
            $rawAp = strtolower(trim((string) ($row->get('ap') ?? '')));
            $flagAp = in_array($rawAp, ['yes', 'ya', '1', 'true', 'y'], true) ? true : (in_array($rawAp, ['no', 'tidak', '0', 'false', 'n'], true) ? false : null);
        }

        $rawStatus = strtolower(trim((string) ($row->get('status') ?? '')));
        $aktif = match ($rawStatus) {
            'inactive', 'nonaktif', 'tidak aktif', '0', 'false', 'no' => false,
            default => true,
        };

        $seenKeys[$kode] = true;

        $existing = MasterItem::where('master_type_id', $this->masterType->id)
            ->where('kode', $kode)
            ->first();

        $isUpdate = $existing !== null;

        return [
            true,
            [
                'master_type_id' => $this->masterType->id,
                'kode' => $kode,
                'nama' => $nama,
                'data' => $data,
                'flag_ar' => $flagAr,
                'flag_ap' => $flagAp,
                'aktif' => $aktif,
                '_is_update' => $isUpdate,
                '_existing_id' => $existing?->id,
            ],
            null,
        ];
    }

    /**
     * Persist a validated master item row (upsert).
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        $isUpdate = $data['_is_update'] ?? false;
        $existingId = $data['_existing_id'] ?? null;

        unset($data['_is_update'], $data['_existing_id']);

        if ($isUpdate && $existingId) {
            MasterItem::where('id', $existingId)->update($data);
        } else {
            MasterItem::create($data);
        }
    }

    /**
     * Normalize a date value.
     */
    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $str = trim((string) $value);

        if (Carbon::hasFormat($str, 'Y-m-d')) {
            return $str;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
