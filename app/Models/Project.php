<?php

namespace App\Models;

use App\Enums\ProjectJenis;
use App\Enums\ProjectStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use App\Services\ReceivableService;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['kode', 'nama', 'lokasi', 'jenis', 'qty', 'satuan', 'harga_satuan', 'pajak', 'tanggal_mulai', 'target_selesai', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, LogsActivity;

    /**
     * Boot the receivable auto-generation for completed projects.
     */
    protected static function booted(): void
    {
        static::created(fn (Project $project) => $project->syncReceivable());
        static::updated(fn (Project $project) => $project->syncReceivable());
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
            'jenis' => ProjectJenis::class,
            'status' => ProjectStatus::class,
            'qty' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'pajak' => 'decimal:2',
            'tanggal_mulai' => 'date',
            'target_selesai' => 'date',
        ];
    }

    /**
     * Contract value without tax (qty x unit price).
     */
    public function getNilaiAttribute(): float
    {
        return (float) $this->qty * (float) $this->harga_satuan;
    }

    /**
     * Tax amount for the contract value.
     */
    public function getNilaiPajakAttribute(): float
    {
        return $this->nilai * ((float) $this->pajak / 100);
    }

    /**
     * Contract value including tax.
     */
    public function getNilaiTotalAttribute(): float
    {
        return $this->nilai + $this->nilai_pajak;
    }

    /**
     * Get the per-akun allocations for the project.
     */
    public function projectAkuns(): HasMany
    {
        return $this->hasMany(ProjectAkun::class);
    }

    /**
     * Get the budget plans for the project.
     */
    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class);
    }

    /**
     * Get the realisasi for the project.
     */
    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }

    /**
     * Get the receivable (piutang) for the project.
     */
    public function receivable(): HasOne
    {
        return $this->hasOne(Receivable::class);
    }

    /**
     * Auto-create a receivable (piutang) when the project is completed.
     */
    public function syncReceivable(): void
    {
        if ($this->status === ProjectStatus::Completed) {
            app(ReceivableService::class)->createForProject($this);
        }
    }

    /**
     * Total budget allocated for the project.
     *
     * Uses the eager-loaded aggregate value when available to avoid extra queries.
     */
    public function getTotalBudgetAttribute(?string $value = null): float
    {
        return $value !== null
            ? (float) $value
            : (float) $this->projectAkuns()->sum('budget');
    }

    /**
     * Total allocation for the project.
     *
     * Uses the eager-loaded aggregate value when available to avoid extra queries.
     */
    public function getTotalAllocationAttribute(?string $value = null): float
    {
        return $value !== null
            ? (float) $value
            : (float) $this->projectAkuns()->where('status', 'approved')->sum('allocation');
    }

    /**
     * Total realized cost for the project.
     *
     * Uses the eager-loaded aggregate value when available to avoid extra queries.
     */
    public function getTotalRealisasiAttribute(?string $value = null): float
    {
        return $value !== null
            ? (float) $value
            : (float) $this->realisasi()->sum('realisasi.nominal');
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Project '.($this->kode ?: '#'.$this->id);
    }
}
