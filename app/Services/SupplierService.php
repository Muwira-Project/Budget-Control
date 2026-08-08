<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    /**
     * Create a new supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    /**
     * Update an existing supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->refresh();
    }

    /**
     * Delete a supplier.
     */
    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    /**
     * List suppliers, optionally filtered by search query.
     */
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Supplier::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage);
    }
}
