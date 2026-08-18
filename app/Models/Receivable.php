<?php

namespace App\Models;

use App\Enums\ReceivableStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use Database\Factories\ReceivableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'tanggal', 'jatuh_tempo', 'nominal', 'nominal_dibayar', 'keterangan', 'hold_reason', 'held_by', 'held_at'])]
class Receivable extends Model
{
    /**
     * Keep the dashboard cache in sync with transaction data.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
        static::restored(fn ($model) => DashboardService::clearCache());

        static::updated(function (Receivable $receivable): void {
            if (! $receivable->wasChanged('nominal')) {
                return;
            }

            $project = $receivable->project;

            if ($project === null || (float) $project->qty <= 0) {
                return;
            }

            $taxFactor = 1 + ((float) $project->pajak / 100);
            $targetPrice = (float) $receivable->nominal / ((float) $project->qty * $taxFactor);

            if (abs((float) $project->harga_satuan - $targetPrice) > 0.009) {
                $project->update(['harga_satuan' => $targetPrice]);
            }
        });
    }

    /** @use HasFactory<ReceivableFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jatuh_tempo' => 'date',
            'nominal' => 'decimal:2',
            'nominal_dibayar' => 'decimal:2',
            'held_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns the receivable.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the payments made against this receivable.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user who put this receivable on hold.
     */
    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    /**
     * Whether this receivable is currently on hold (K1: tahan penerimaan).
     */
    public function isHeld(): bool
    {
        return $this->held_at !== null;
    }

    /**
     * Sisa tagihan = nominal - nominal_dibayar.
     */
    public function getSisaAttribute(): float
    {
        return (float) $this->nominal - (float) $this->nominal_dibayar;
    }

    /**
     * Status dihitung otomatis dari nominal vs nominal_dibayar.
     */
    public function getStatusAttribute(): ReceivableStatus
    {
        if ((float) $this->nominal_dibayar >= (float) $this->nominal) {
            return ReceivableStatus::Lunas;
        }

        return (float) $this->nominal_dibayar > 0
            ? ReceivableStatus::Sebagian
            : ReceivableStatus::BelumDibayar;
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Receivable #'.$this->id;
    }
}
