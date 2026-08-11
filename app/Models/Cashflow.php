<?php

namespace App\Models;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use Database\Factories\CashflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tanggal', 'jenis', 'sumber', 'payment_request_id', 'payment_id', 'nominal', 'keterangan'])]
class Cashflow extends Model
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

    /** @use HasFactory<CashflowFactory> */
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
            'jenis' => CashflowJenis::class,
            'sumber' => CashflowSumber::class,
            'nominal' => 'decimal:2',
        ];
    }

    /**
     * Get the payment request that triggered this cashflow entry.
     */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /**
     * Determine whether this is a manually recorded entry.
     */
    public function isManual(): bool
    {
        return $this->payment_request_id === null && $this->payment_id === null;
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Cashflow #'.$this->id;
    }
}
