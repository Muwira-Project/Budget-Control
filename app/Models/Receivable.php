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

#[Fillable(['project_id', 'tanggal', 'jatuh_tempo', 'nominal', 'nominal_dibayar', 'keterangan'])]
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
    }

    /** @use HasFactory<ReceivableFactory> */
    use HasFactory, LogsActivity;

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
