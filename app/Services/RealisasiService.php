<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Project;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;

class RealisasiService
{
    /**
     * List the actual ledger, optionally filtered by project, akun, and date range.
     */
    public function paginate(?Project $project = null, ?Akun $akun = null, ?string $startDate = null, ?string $endDate = null, int $perPage = 10): LengthAwarePaginator
    {
        return Realisasi::query()
            ->with(['akun', 'project', 'pihakType', 'pihakItem', 'kategori'])
            ->when($akun, fn ($query) => $query->where('akun_id', $akun->id))
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->latest('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }
}
