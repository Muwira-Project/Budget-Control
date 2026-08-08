<?php

namespace App\Models;

use App\Enums\AllocationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'akun_id', 'budget', 'allocation', 'status', 'created_by', 'approved_by', 'approved_at'])]
class ProjectAkun extends Model
{
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
     * Determine whether the allocation has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === AllocationStatus::Approved;
    }

    /**
     * Get the realisasi for this project-akun.
     */
    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class, 'project_id', 'project_id');
    }

    /**
     * Total realized amount for this project-akun.
     */
    public function getTotalRealisasiAttribute(?string $value = null): float
    {
        return $value !== null
            ? (float) $value
            : (float) $this->realisasi()->where('akun_id', $this->akun_id)->sum('nominal');
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
