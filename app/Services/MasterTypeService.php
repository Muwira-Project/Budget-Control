<?php

namespace App\Services;

use App\Models\MasterType;
use Illuminate\Pagination\LengthAwarePaginator;

class MasterTypeService
{
    /**
     * Create a dynamic master type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MasterType
    {
        return MasterType::create([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'flag_ar' => $data['flag_ar'] ?? false,
            'flag_ap' => $data['flag_ap'] ?? false,
            'aktif' => $data['aktif'] ?? true,
        ]);
    }

    /**
     * Update a master type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MasterType $type, array $data): MasterType
    {
        $type->update($data);

        return $type->refresh();
    }

    /**
     * Delete a master type (items cascade).
     */
    public function delete(MasterType $type): void
    {
        $type->delete();
    }

    /**
     * List master types.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return MasterType::query()
            ->withCount('items')
            ->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString();
    }
}
