<?php

namespace App\Services;

use App\Models\MasterType;
use Illuminate\Pagination\LengthAwarePaginator;

class MasterTypeService
{
    /**
     * Create a dynamic master type (menu) with its field definitions.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MasterType
    {
        $type = MasterType::create([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'flag_ar' => $data['flag_ar'] ?? false,
            'flag_ap' => $data['flag_ap'] ?? false,
            'aktif' => $data['aktif'] ?? true,
        ]);

        $this->syncFields($type, $data['fields'] ?? []);

        return $type->refresh();
    }

    /**
     * Update a master type and its field definitions.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MasterType $type, array $data): MasterType
    {
        $type->update([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'flag_ar' => $data['flag_ar'] ?? false,
            'flag_ap' => $data['flag_ap'] ?? false,
            'aktif' => $data['aktif'] ?? true,
        ]);

        $this->syncFields($type, $data['fields'] ?? []);

        return $type->refresh();
    }

    /**
     * Delete a master type (items and fields cascade).
     */
    public function delete(MasterType $type): void
    {
        $type->delete();
    }

    /**
     * List master types (master menus).
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return MasterType::query()
            ->withCount(['items', 'fields'])
            ->orderBy('is_system')
            ->orderBy('sort')
            ->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Replace the field definitions of a master type.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncFields(MasterType $type, array $fields): void
    {
        $type->fields()->delete();

        $rows = [];

        foreach (array_values($fields) as $index => $field) {
            $label = trim((string) ($field['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'tipe' => in_array($field['tipe'] ?? 'text', ['text', 'textarea', 'number', 'date'], true) ? $field['tipe'] : 'text',
                'is_required' => (bool) ($field['is_required'] ?? false),
                'sort' => $index,
            ];
        }

        foreach ($rows as $row) {
            $type->fields()->create($row);
        }
    }
}
