<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Project;
use App\Models\Receivable;
use App\Enums\ProjectStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableService
{
    /**
     * Guard: reject a duplicate active invoice number (case-insensitive).
     * Soft-deleted rows are ignored so a trashed record can be re-entered.
     */
    private function ensureUniqueInvoiceNumber(?string $nomorInvoice, ?int $ignoreId = null): void
    {
        if ($nomorInvoice === null || trim($nomorInvoice) === '') {
            return;
        }

        $exists = Receivable::query()
            ->whereRaw('LOWER(TRIM(nomor_invoice)) = ?', [mb_strtolower(trim($nomorInvoice))])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nomor_invoice' => 'Nomor invoice sudah digunakan pada receivable lain.',
            ]);
        }
    }

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

        $this->ensureUniqueInvoiceNumber($data['nomor_invoice'] ?? null);

        $receivable = Receivable::create([
            'project_id' => $data['project_id'],
            'pihak_type_id' => $data['pihak_type_id'] ?? null,
            'pihak_item_id' => $data['pihak_item_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'nomor_invoice' => $data['nomor_invoice'] ?? null,
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'nominal_dibayar' => 0,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        app(NotificationService::class)->notifyReceivableChanged($receivable, 'created');

        return $receivable;
    }

    /**
     * Update an existing receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Receivable $receivable, array $data): Receivable
    {
        // Guard: the nominal can never drop below the amount already paid,
        // otherwise the outstanding balance (sisa) turns negative and the
        // AR aging/reporting silently corrupts.
        $newNominal = (float) ($data['nominal'] ?? $receivable->nominal);

        if ($newNominal < (float) $receivable->nominal_dibayar) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal cannot be lower than the amount already paid ('.number_format((float) $receivable->nominal_dibayar, 0, ',', '.').').',
            ]);
        }

        $this->ensureUniqueInvoiceNumber($data['nomor_invoice'] ?? $receivable->nomor_invoice, $receivable->id);

        $receivable->update([
            'project_id' => $data['project_id'],
            'pihak_type_id' => $data['pihak_type_id'] ?? $receivable->pihak_type_id,
            'pihak_item_id' => $data['pihak_item_id'] ?? $receivable->pihak_item_id,
            'tanggal' => $data['tanggal'],
            'nomor_invoice' => $data['nomor_invoice'] ?? $receivable->nomor_invoice,
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        app(NotificationService::class)->notifyReceivableChanged($receivable->refresh(), 'updated');

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
     * Delete a receivable. Any payments linked to it are removed as well so the
     * AR balance stays consistent (the FK cascade no longer applies because
     * payments use soft deletes).
     */
    public function delete(Receivable $receivable): void
    {
        DB::transaction(function () use ($receivable): void {
            Payment::where('receivable_id', $receivable->id)->get()->each(fn (Payment $payment) => app(PaymentService::class)->delete($payment));

            $receivable->delete();
        });
    }

    /**
     * List receivables, optionally filtered by project and status.
     */
    public function paginate(
        ?Project $project = null,
        ?string $status = null,
        ?string $aging = null,
        ?string $arCategory = null,
        ?string $poNumber = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?float $amountMin = null,
        ?float $amountMax = null,
        int $perPage = 10
    ): LengthAwarePaginator
    {
        return Receivable::query()
            ->with(['project', 'pihakType', 'pihakItem'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $this->applyStatusFilter($query, $status))
            ->when($aging, fn ($query) => $this->applyAgingFilter($query, $aging))
            ->when($arCategory, fn ($query) => $this->applyArCategoryFilter($query, $arCategory))
            ->when($poNumber, fn ($query) => $query->whereHas('project', fn ($q) => $q->where('po_number', 'like', "%{$poNumber}%")))
            ->when($dateFrom, fn ($query) => $query->whereDate('tanggal', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('tanggal', '<=', $dateTo))
            ->when($amountMin !== null, fn ($query) => $query->where('nominal', '>=', $amountMin))
            ->when($amountMax !== null, fn ($query) => $query->where('nominal', '<=', $amountMax))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Apply the AR category filter to the query (based on project status and PO number).
     */
    protected function applyArCategoryFilter($query, string $arCategory)
    {
        return $query->whereHas('project', function ($q) use ($arCategory) {
            match ($arCategory) {
                'billed' => $q->where('status', ProjectStatus::Done)->whereNotNull('po_number'),
                'unbilled' => $q->where('status', ProjectStatus::Done)->whereNull('po_number'),
                'inprogress' => $q->where('status', '!=', ProjectStatus::Done),
                default => $q,
            };
        });
    }

    /**
     * Apply the aging bucket filter to the query (based on due date).
     *
     * Buckets: current (not due yet), 1_30, 31_60, 61_90, over_90.
     */
    protected function applyAgingFilter($query, string $aging)
    {
        $today = now()->startOfDay();

        return match ($aging) {
            'current' => $query->where(fn ($q) => $q->whereNull('jatuh_tempo')->orWhereDate('jatuh_tempo', '>=', $today)),
            '1_30' => $query->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(30))->whereDate('jatuh_tempo', '<', $today),
            '31_60' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(30))->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(60)),
            '61_90' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(60))->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(90)),
            'over_90' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(90)),
            default => $query,
        };
    }

    /**
     * Auto-create a receivable for a completed project (idempotent).
     * Uses updateOrCreate withTrashed to handle project status changes (Done -> Revisi -> Done).
     * Also handles soft-deleted receivables by restoring them.
     */
    public function createForProject(Project $project): ?Receivable
    {
        if (! $project->status->isDone()) {
            return null;
        }

        // First check if there's a soft-deleted receivable for this project
        $trashedReceivable = Receivable::onlyTrashed()->where('project_id', $project->id)->first();
        
        if ($trashedReceivable) {
            // Restore the soft-deleted receivable
            $trashedReceivable->restore();
            $trashedReceivable->update([
                'tanggal' => now()->toDateString(),
                'jatuh_tempo' => null,
                'nominal' => $project->nilai_total,
                'nominal_dibayar' => 0,
                'keterangan' => 'Receivable from contract ' . $project->kode,
                'pihak_type_id' => $project->customer_type_id ?? null,
                'pihak_item_id' => $project->customer_item_id ?? null,
                'nomor_invoice' => $this->generateInvoiceNumber($project),
            ]);
            return $trashedReceivable->refresh();
        }

        $receivable = Receivable::updateOrCreate(
            ['project_id' => $project->id],
            [
                'tanggal' => now()->toDateString(),
                'jatuh_tempo' => null,
                'nominal' => $project->nilai_total,
                'nominal_dibayar' => 0,
                'keterangan' => 'Receivable from contract ' . $project->kode,
                'pihak_type_id' => $project->customer_type_id ?? null,
                'pihak_item_id' => $project->customer_item_id ?? null,
                'nomor_invoice' => $this->generateInvoiceNumber($project),
            ]
        );

        return $receivable;
    }

    /**
     * Generate a unique invoice number for the project.
     */
    private function generateInvoiceNumber(Project $project): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $sequence = Receivable::whereYear('tanggal', $year)->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
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

    /**
     * Get AR summary grouped by category (billed, unbilled, inprogress) with totals.
     *
     * @return array<string, array{nominal: float, paid: float, outstanding: float, count: int}>
     */
    public function getArSummary(
        ?string $arCategory = null,
        ?string $poNumber = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?float $amountMin = null,
        ?float $amountMax = null
    ): array {
        $categories = ['billed', 'unbilled', 'inprogress'];
        
        $summary = [];
        $grandTotal = ['nominal' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'count' => 0];

        foreach ($categories as $category) {
            if ($arCategory && $arCategory !== $category) {
                $summary[$category] = ['nominal' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'count' => 0];
                continue;
            }

            $query = Receivable::query()->whereHas('project', function ($q) use ($category) {
                match ($category) {
                    'billed' => $q->where('status', ProjectStatus::Done)->whereNotNull('po_number'),
                    'unbilled' => $q->where('status', ProjectStatus::Done)->whereNull('po_number'),
                    'inprogress' => $q->where('status', '!=', ProjectStatus::Done),
                    default => $q,
                };
            });

            // Apply same filters as paginate
            if ($poNumber) {
                $query->whereHas('project', fn ($q) => $q->where('po_number', 'like', "%{$poNumber}%"));
            }
            if ($dateFrom) {
                $query->whereDate('tanggal', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('tanggal', '<=', $dateTo);
            }
            if ($amountMin !== null) {
                $query->where('nominal', '>=', $amountMin);
            }
            if ($amountMax !== null) {
                $query->where('nominal', '<=', $amountMax);
            }

            $aggregates = $query->selectRaw(
                'COALESCE(SUM(nominal), 0) as total_nominal,
                 COALESCE(SUM(nominal_dibayar), 0) as total_paid,
                 COALESCE(SUM(nominal - nominal_dibayar), 0) as total_outstanding,
                 COUNT(*) as total_count'
            )->first();

            $summary[$category] = [
                'nominal' => (float) $aggregates->total_nominal,
                'paid' => (float) $aggregates->total_paid,
                'outstanding' => (float) $aggregates->total_outstanding,
                'count' => (int) $aggregates->total_count,
            ];

            $grandTotal['nominal'] += $summary[$category]['nominal'];
            $grandTotal['paid'] += $summary[$category]['paid'];
            $grandTotal['outstanding'] += $summary[$category]['outstanding'];
            $grandTotal['count'] += $summary[$category]['count'];
        }

        $summary['grand_total'] = $grandTotal;

        return $summary;
    }
}
