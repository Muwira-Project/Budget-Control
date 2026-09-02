<?php

namespace App\Services;

use App\Exceptions\ItemInUseException;
use App\Models\MasterItem;
use App\Models\MasterType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            'data' => $data['data'] ?? null,
            'aktif' => $data['aktif'] ?? true,
            'flag_ar' => $data['flag_ar'] ?? null,
            'flag_ap' => $data['flag_ap'] ?? null,
        ]);
    }

    /**
     * Update a master item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MasterItem $item, array $data): MasterItem
    {
        $item->update([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
            'data' => $data['data'] ?? null,
            'aktif' => $data['aktif'] ?? true,
            'flag_ar' => $data['flag_ar'] ?? $item->flag_ar,
            'flag_ap' => $data['flag_ap'] ?? $item->flag_ap,
        ]);

        return $item->refresh();
    }

    /**
     * Delete a master item.
     */
    public function delete(MasterItem $item): void
    {
        $inUse = DB::table('realisasi')
            ->where('pihak_item_id', $item->id)
            ->whereNull('deleted_at')
            ->exists()
            || DB::table('payables')
                ->where('pihak_item_id', $item->id)
                ->whereNull('deleted_at')
                ->exists();

        if ($inUse) {
            throw new ItemInUseException("Item #{$item->id} is in use by transactions.");
        }

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
