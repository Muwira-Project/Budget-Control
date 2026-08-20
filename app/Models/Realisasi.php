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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'akun_id', 'pihak_type_id', 'pihak_item_id', 'kategori_id', 'tanggal', 'nominal', 'keterangan', 'sumber', 'sumber_id'])]
class Realisasi extends Model
{
    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_AP_PAYMENT = 'pelunasan_ap';

    public const SUMBER_AR_PAYMENT = 'pelunasan_ar';

    /** @use HasFactory<RealisasiFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'realisasi';

    /**
     * Keep the dashboard cache in sync and auto-generate payables from
     * project realisasi.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
        static::restored(fn ($model) => DashboardService::clearCache());

        static::saving(function (Realisasi $realisasi): void {
            $partyCount = collect([
                $realisasi->pihak_type_id,
                $realisasi->pihak_item_id,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 0 && $partyCount !== 2) {
                throw new \InvalidArgumentException('Party type and party item must be provided together.');
            }
        });

        static::created(function (Realisasi $realisasi): void {
            app(NotificationService::class)->notifyIfOverBudget($realisasi);

            if ($realisasi->sumber === null || $realisasi->sumber === self::SUMBER_MANUAL) {
                app(PayableService::class)->syncFromRealisasi($realisasi);
            }
        });

        static::updated(function (Realisasi $realisasi): void {
            app(NotificationService::class)->notifyIfOverBudget($realisasi);

            if ($realisasi->sumber === null || $realisasi->sumber === self::SUMBER_MANUAL) {
                app(PayableService::class)->syncFromRealisasi($realisasi->refresh());
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
            'nominal' => 'decimal:2',
        ];
    }

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the akun (COA).
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Get the category.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    /**
     * Get the party type (Vendor/Supplier/Mandor/Investor).
     */
    public function pihakType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class, 'pihak_type_id');
    }

    /**
     * Get the party item (the concrete vendor/supplier/… record).
     */
    public function pihakItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'pihak_item_id');
    }

    /**
     * The manually-created payable for this realisasi.
     */
    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class);
    }

    /**
     * Display label of the party (vendor, supplier, mandor, investor).
     */
    public function getPihakAttribute(): ?string
    {
        return $this->pihakItem?->nama;
    }

    /**
     * Normalized party kind ('vendor', 'supplier', 'mandor', 'investor').
     */
    public function getPihakJenisAttribute(): ?string
    {
        return $this->pihakType?->kode ? strtolower($this->pihakType->kode) : null;
    }

    /**
     * True when this row is an AR/AP settlement (auto-generated).
     */
    public function getIsPaymentAttribute(): bool
    {
        return $this->sumber !== null;
    }
}
