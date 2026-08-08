<?php

namespace App\Services;

use App\Models\Kategori;
use Illuminate\Pagination\LengthAwarePaginator;

class KategoriService
{
    /**
     * Create a new kategori.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Kategori
    {
        return Kategori::create($data);
    }

    /**
     * Update an existing kategori.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Kategori $kategori, array $data): Kategori
    {
        $kategori->update($data);

        return $kategori->refresh();
    }

    /**
     * Delete a kategori.
     */
    public function delete(Kategori $kategori): void
    {
        $kategori->delete();
    }

    /**
     * List kategoris, optionally filtered by search query.
     */
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Kategori::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage);
    }
}
