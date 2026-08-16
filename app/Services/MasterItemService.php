<?php

namespace App\Services;

use App\Models\MasterItem;
use App\Models\MasterType;
use Illuminate\Pagination\LengthAwarePaginator;

class MasterItemService
{
    /**
     * Create a master item under a type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MasterItem
    {
        return MasterItem::create([
            'master_type_id' => $data['master_type_id'],
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
            'aktif' => $data['aktif'] ?? true,
        ]);
    }

    /**
     * Update a master item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MasterItem $item, array $data): MasterItem
    {
        $item->update($data);

        return $item->refresh();
    }

    /**
     * Delete a master item.
     */
    public function delete(MasterItem $item): void
    {
        $item->delete();
    }

    /**
     * List master items for a type.
     */
    public function paginate(MasterType $type, string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        return MasterItem::query()
            ->with('masterType')
            ->where('master_type_id', $type->id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage)
            ->withQueryString();
    }
}
