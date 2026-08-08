<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Pagination\LengthAwarePaginator;

class VendorService
{
    /**
     * Create a new vendor.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    /**
     * Update an existing vendor.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);

        return $vendor->refresh();
    }

    /**
     * Delete a vendor.
     */
    public function delete(Vendor $vendor): void
    {
        $vendor->delete();
    }

    /**
     * List vendors, optionally filtered by search query.
     */
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Vendor::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage);
    }
}
