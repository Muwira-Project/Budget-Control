<?php

namespace App\Services;

use App\Models\Akun;
use Illuminate\Pagination\LengthAwarePaginator;

class AkunService
{
    /**
     * Create a new akun.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Akun
    {
        return Akun::create($data);
    }

    /**
     * Update an existing akun.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Akun $akun, array $data): Akun
    {
        $akun->update($data);

        return $akun->refresh();
    }

    /**
     * Delete an akun.
     */
    public function delete(Akun $akun): void
    {
        $akun->delete();
    }

    /**
     * List master akuns, optionally filtered by search and jenis.
     */
    public function paginate(string $search = '', ?string $jenisAkun = null, ?string $startDate = null, ?string $endDate = null, int $perPage = 10): LengthAwarePaginator
    {
        return Akun::query()
            ->with('kategori')
            ->withSum(['realisasi as actual_realtime' => function ($query) use ($startDate, $endDate) {
                $query->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal', '<=', $endDate));
            }], 'nominal')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode_akun', 'like', '%'.$search.'%')
                    ->orWhere('nama_akun', 'like', '%'.$search.'%');
            }))
            ->when($jenisAkun !== null, fn ($query) => $query->where('jenis_akun', $jenisAkun))
            ->orderBy('jenis_akun')
            ->orderBy('kode_akun')
            ->paginate($perPage);
    }
}
