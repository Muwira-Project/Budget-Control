<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\BudgetPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'periode', 'nomor', 'estimasi_pendapatan', 'estimasi_biaya', 'target_laba'])]
class BudgetPlan extends Model
{
    /** @use HasFactory<BudgetPlanFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimasi_pendapatan' => 'decimal:2',
            'estimasi_biaya' => 'decimal:2',
            'target_laba' => 'decimal:2',
        ];
    }

    /**
     * Get the project that owns the budget plan.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the budget plan items (rincian per akun).
     */
    public function items(): HasMany
    {
        return $this->hasMany(BudgetPlanItem::class);
    }

    /**
     * Total budget from all items.
     */
    public function getTotalBudgetAttribute(): float
    {
        return (float) ($this->relationLoaded('items')
            ? $this->items->sum('nominal')
            : $this->items()->sum('nominal'));
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Budget '.($this->periode ?: '#'.$this->id);
    }
}
