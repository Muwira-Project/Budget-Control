<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Project;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;

class RealisasiService
{
    /**
     * Create a new realisasi.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Realisasi
    {
        return Realisasi::create($data);
    }

    /**
     * Update an existing realisasi.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Realisasi $realisasi, array $data): Realisasi
    {
        $realisasi->update($data);

        return $realisasi->refresh();
    }

    /**
     * Delete a realisasi.
     */
    public function delete(Realisasi $realisasi): void
    {
        $realisasi->delete();
    }

    /**
     * List realisasi, optionally filtered by project, akun, and date range.
     */
    public function paginate(?Project $project = null, ?Akun $akun = null, ?string $startDate = null, ?string $endDate = null, int $perPage = 10): LengthAwarePaginator
    {
        return Realisasi::query()
            ->with(['akun', 'project', 'vendor', 'supplier', 'mandor', 'investor', 'kategori'])
            ->when($akun, fn ($query) => $query->where('akun_id', $akun->id))
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->latest('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }
}
