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

#[Fillable(['project_id', 'realisasi_id', 'payment_request_id', 'akun_id', 'vendor_id', 'supplier_id', 'mandor_id', 'investor_id', 'tanggal', 'jatuh_tempo', 'nominal', 'jenis_pajak', 'pajak_include', 'nominal_dibayar', 'keterangan'])]
class Payable extends Model
{
    /** @use HasFactory<PayableFactory> */
    use HasFactory, LogsActivity;

    /**
     * Ensure a payable always has one, and only one, counterparty.
     */
    protected static function booted(): void
    {
        static::saving(function (Payable $payable): void {
            $partyCount = collect([
                $payable->vendor_id,
                $payable->supplier_id,
                $payable->mandor_id,
                $payable->investor_id,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 1) {
                throw new \InvalidArgumentException('A payable must reference exactly one party.');
            }
        });
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());

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
     * Get the payment request that generated this payable (when synced).
     */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /**
     * Get the master akun.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }

    /**
     * Get the vendor for the payable.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the supplier for the payable.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function mandor(): BelongsTo
    {
        return $this->belongsTo(Mandor::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
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
     * Display label of the party (vendor or supplier).
     */
    public function getPihakAttribute(): ?string
    {
        return $this->vendor?->nama ?? $this->supplier?->nama ?? $this->mandor?->nama ?? $this->investor?->nama;
    }

    /**
     * Display label of the party type: vendor or supplier.
     */
    public function getPihakJenisAttribute(): ?string
    {
        if ($this->vendor_id !== null) {
            return 'vendor';
        }

        if ($this->supplier_id !== null) {
            return 'supplier';
        }

        if ($this->mandor_id !== null) {
            return 'mandor';
        }

        if ($this->investor_id !== null) {
            return 'investor';
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
