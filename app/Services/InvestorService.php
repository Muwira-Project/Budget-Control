<?php

namespace App\Services;

use App\Models\Investor;
use Illuminate\Pagination\LengthAwarePaginator;

class InvestorService
{
    public function create(array $data): Investor
    {
        return Investor::create($data);
    }

    public function update(Investor $investor, array $data): Investor
    {
        $investor->update($data);

        return $investor->refresh();
    }

    public function delete(Investor $investor): void
    {
        $investor->delete();
    }

    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Investor::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            }))
            ->orderBy('kode')
            ->paginate($perPage);
    }
}
