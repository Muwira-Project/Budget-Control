<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Receivable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReceivableService
{
    /**
     * Create a receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Receivable
    {
        // Uniqueness is enforced here because SQLite cannot express a
        // partial unique index for active (non-soft-deleted) rows.
        if (Receivable::where('project_id', $data['project_id'])->exists()) {
            throw ValidationException::withMessages(['project_id' => 'Project ini sudah memiliki piutang.']);
        }

        return Receivable::create([
            'project_id' => $data['project_id'],
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'nominal_dibayar' => 0,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Update an existing receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Receivable $receivable, array $data): Receivable
    {
        $receivable->update([
            'project_id' => $data['project_id'],
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return $receivable->refresh();
    }

    /**
     * Put a receivable on hold (K1: tahan penerimaan yang belum tercatat).
     */
    public function hold(Receivable $receivable, string $reason): Receivable
    {
        if ($receivable->isHeld()) {
            return $receivable;
        }

        $receivable->update([
            'hold_reason' => $reason,
            'held_by' => auth()->id(),
            'held_at' => now(),
        ]);

        return $receivable->refresh();
    }

    /**
     * Release a held receivable.
     */
    public function release(Receivable $receivable): Receivable
    {
        $receivable->update([
            'hold_reason' => null,
            'held_by' => null,
            'held_at' => null,
        ]);

        return $receivable->refresh();
    }

    /**
     * Delete a receivable (payments are removed by the database cascade).
     */
    public function delete(Receivable $receivable): void
    {
        $receivable->delete();
    }

    /**
     * List receivables, optionally filtered by project and status.
     */
    public function paginate(?Project $project = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Receivable::query()
            ->with('project')
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $this->applyStatusFilter($query, $status))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Auto-create a receivable for a completed project (idempotent).
     */
    public function createForProject(Project $project): ?Receivable
    {
        if (Receivable::where('project_id', $project->id)->exists()) {
            return null;
        }

        return Receivable::create([
            'project_id' => $project->id,
            'tanggal' => now()->toDateString(),
            'jatuh_tempo' => null,
            'nominal' => $project->nilai_total,
            'nominal_dibayar' => 0,
            'keterangan' => 'Receivable from contract '.$project->kode,
        ]);
    }

    /**
     * Apply the computed status filter to the query.
     */
    protected function applyStatusFilter($query, string $status)
    {
        return match ($status) {
            'belum_dibayar' => $query->where('nominal_dibayar', 0),
            'lunas' => $query->whereColumn('nominal_dibayar', '>=', 'nominal'),
            'sebagian' => $query->where('nominal_dibayar', '>', 0)->whereColumn('nominal_dibayar', '<', 'nominal'),
            default => $query,
        };
    }
}
