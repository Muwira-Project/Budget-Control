<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use App\Services\NotificationService;
use App\Services\PayableService;
use Database\Factories\RealisasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'akun_id', 'vendor_id', 'supplier_id', 'mandor_id', 'investor_id', 'kategori_id', 'tanggal', 'nominal', 'keterangan', 'sumber', 'sumber_id'])]
class Realisasi extends Model
{
    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_AP_PAYMENT = 'pelunasan_ap';

    /** @use HasFactory<RealisasiFactory> */
    use HasFactory, LogsActivity;

    /**
     * Boot the payable auto-generation from realisasi.
     */
    protected static function booted(): void
    {
        static::saving(function (Realisasi $realisasi): void {
            $partyCount = collect([
                $realisasi->vendor_id,
                $realisasi->supplier_id,
                $realisasi->mandor_id,
                $realisasi->investor_id,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 1) {
                throw new \InvalidArgumentException('A realisasi must reference exactly one party.');
            }
        });

        static::created(fn (Realisasi $realisasi) => $realisasi->sumber === null
            ? app(PayableService::class)->syncFromRealisasi($realisasi)
            : null);
        static::updated(fn (Realisasi $realisasi) => $realisasi->sumber === null
            ? app(PayableService::class)->syncFromRealisasi($realisasi)
            : null);

        static::created(function (Realisasi $realisasi): void {
            app(NotificationService::class)->notifyIfOverBudget($realisasi);
        });

        static::updated(function (Realisasi $realisasi): void {
            app(NotificationService::class)->notifyIfOverBudget($realisasi);
        });

        // Clear dashboard cache when realisasi is created, updated, or deleted
        static::created(fn (Realisasi $realisasi) => DashboardService::clearCache());
        static::updated(fn (Realisasi $realisasi) => DashboardService::clearCache());
        static::deleted(fn (Realisasi $realisasi) => DashboardService::clearCache());
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'realisasi';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    /**
     * Get the project that owns the realisasi.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the akun that owns the realisasi.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }

    /**
     * Get the vendor for the realisasi.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the supplier for the realisasi.
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
     * Display label of the transaction party (vendor or supplier).
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
     * Get the kategori for the realisasi.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /**
     * Short label used in the activity log.
     */
    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Actual #'.$this->id;
    }

    /**
     * Display label of the actual source (auto-generated vs manual).
     */
    public function getSumberLabelAttribute(): string
    {
        return match ($this->sumber) {
            self::SUMBER_AP_PAYMENT => 'AP Payment',
            default => 'Manual',
        };
    }
}
