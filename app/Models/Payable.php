<?php

namespace App\Models;

use App\Enums\PajakJenis;
use App\Enums\PayableStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use Database\Factories\PayableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'realisasi_id', 'akun_id', 'pihak_type_id', 'pihak_item_id', 'tanggal', 'nomor_invoice', 'jatuh_tempo', 'nominal', 'jenis_pajak', 'pajak_include', 'nominal_dibayar', 'keterangan'])]
class Payable extends Model
{
    /** @use HasFactory<PayableFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Ensure a payable always has a counterparty (both parts of the party pair),
     * unless it is a legacy row (old FK columns may still hold the data).
     */
    protected static function booted(): void
    {
        static::saving(function (Payable $payable): void {
            if ($payable->pihak_type_id === null || $payable->pihak_item_id === null) {
                throw new \InvalidArgumentException('A payable must reference exactly one party (pihak_type_id + pihak_item_id).');
            }
        });
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
        static::restored(fn ($model) => DashboardService::clearCache());

        static::updated(function (Payable $payable): void {
            if (! $payable->wasChanged('nominal') || $payable->realisasi_id === null) {
                return;
            }

            $realisasi = $payable->realisasi;

            if ($realisasi !== null && (float) $realisasi->nominal !== (float) $payable->nominal) {
                $realisasi->update(['nominal' => $payable->nominal]);
            }
        });
    }

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
            'jenis_pajak' => PajakJenis::class,
            'pajak_include' => 'boolean',
        ];
    }

    /**
     * Get the project that owns the payable.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the realisasi that generated this payable (when auto-created).
     */
    public function realisasi(): BelongsTo
    {
        return $this->belongsTo(Realisasi::class);
    }

    /**
     * Get the master akun.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }

    /**
     * Get the party type (Vendor/Supplier/Mandor/Investor) of the payable.
     */
    public function pihakType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class, 'pihak_type_id');
    }

    /**
     * Get the party item (the concrete vendor/supplier/… record) of the payable.
     */
    public function pihakItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'pihak_item_id');
    }

    /**
     * Get the payments made against this payable.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Sisa hutang = nominal - nominal_dibayar.
     */
    public function getSisaAttribute(): float
    {
        return (float) $this->nominal - (float) $this->nominal_dibayar;
    }

    /**
     * Status dihitung otomatis dari nominal vs nominal_dibayar.
     */
    public function getStatusAttribute(): PayableStatus
    {
        if ((float) $this->nominal_dibayar >= (float) $this->nominal) {
            return PayableStatus::Lunas;
        }

        return (float) $this->nominal_dibayar > 0
            ? PayableStatus::Sebagian
            : PayableStatus::BelumBayar;
    }

    /**
     * Display label of the party (vendor, supplier, mandor, or investor).
     */
    public function getPihakAttribute(): ?string
    {
        return $this->pihakItem?->nama;
    }

    /**
     * Display label of the party type: vendor, supplier, mandor, or investor.
     */
    public function getPihakJenisAttribute(): ?string
    {
        if ($this->pihak_type_id !== null && $this->pihakType !== null) {
            return strtolower((string) $this->pihakType->kode);
        }

        return null;
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Payable #'.$this->id;
    }
}
