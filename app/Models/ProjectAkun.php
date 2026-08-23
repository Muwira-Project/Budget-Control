<?php

namespace App\Models;

use App\Enums\AllocationStatus;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'akun_id', 'type', 'pihak_type_id', 'pihak_item_id', 'payable_id', 'receivable_id', 'custom_name', 'outstanding_balance', 'budget', 'allocation', 'status', 'created_by', 'approved_by', 'approved_at'])]
class ProjectAkun extends Model
{
    /**
     * Keep the dashboard cache in sync with transaction data.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'allocation' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'status' => AllocationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Scope the query to approved allocations only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', AllocationStatus::Approved);
    }

    /**
     * Get the project that owns this allocation.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the master akun.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Get the user who approved this allocation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this draft.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the party type (Vendor/Supplier/Mandor/Investor) for non-project AP/AR.
     */
    public function pihakType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class, 'pihak_type_id');
    }

    /**
     * Get the party item for non-project AP/AR.
     */
    public function pihakItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'pihak_item_id');
    }

    /**
     * Get the linked payable (for AP type).
     */
    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    /**
     * Get the linked receivable (for AR type).
     */
    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    /**
     * Display label of the party for non-project AP/AR.
     */
    public function getPihakAttribute(): ?string
    {
        return $this->pihakItem?->nama;
    }

    /**
     * Display label of the party type for non-project AP/AR.
     */
    public function getPihakJenisAttribute(): ?string
    {
        if ($this->pihak_type_id !== null && $this->pihakType !== null) {
            return strtolower((string) $this->pihakType->kode);
        }

        return null;
    }

    /**
     * Type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'ap' => 'AP (Hutang)',
            'ar' => 'AR (Piutang)',
            'other_income' => 'Other Income',
            'other_outcome' => 'Other Outcome',
            default => $this->type ?? 'Other Outcome',
        };
    }

    /**
     * Display name: party name or custom name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->custom_name ?? $this->pihakItem?->nama ?? '-';
    }

    /**
     * Determine whether the allocation has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === AllocationStatus::Approved;
    }

    /**
     * Get the realisasi for this project-akun.
     *
     * Plain hasMany keyed on project_id (NULL matches non-project rows).
     * Consumers filter by akun_id where needed — instance-based constraint
     * on the relation breaks eager loading for mixed result sets.
     */
    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class, 'project_id', 'project_id');
    }

    /**
     * Total realized amount for this project-akun.
     *
     * Filters by akun_id explicitly since the relation only keys on
     * project_id (NULL = non-project rows).
     */
    public function getTotalRealisasiAttribute(?string $value = null): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) Realisasi::query()
            ->where('project_id', $this->project_id)
            ->where('akun_id', $this->akun_id)
            ->sum('nominal');
    }

    /**
     * Variance = Budget - Realisasi.
     */
    public function getVarianceAttribute(): float
    {
        return (float) $this->budget - $this->total_realisasi;
    }

    /**
     * Remaining Allocation = Allocation - Realisasi.
     */
    public function getRemainingAllocationAttribute(): float
    {
        return (float) $this->allocation - $this->total_realisasi;
    }

    /**
     * Available Budget = Budget - Allocation.
     */
    public function getAvailableBudgetAttribute(): float
    {
        return (float) $this->budget - $this->allocation;
    }
}
