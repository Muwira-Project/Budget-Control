<?php

namespace App\Services;

use App\Models\Mandor;
use Illuminate\Pagination\LengthAwarePaginator;

class MandorService
{
    public function create(array $data): Mandor
    {
        return Mandor::create($data);
    }

    public function update(Mandor $mandor, array $data): Mandor
    {
        $mandor->update($data);

        return $mandor->refresh();
    }

    public function delete(Mandor $mandor): void
    {
        $mandor->delete();
    }

    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Mandor::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage);
    }
}
