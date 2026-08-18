<?php

namespace App\Models;

use App\Enums\PaymentJenis;
use App\Enums\SettlementStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['receivable_id', 'payable_id', 'tanggal', 'nominal', 'jenis', 'keterangan', 'status', 'void_reason', 'void_requested_by', 'void_requested_at', 'void_review_note', 'void_reviewed_by', 'void_reviewed_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * A payment settles exactly one receivable or payable.
     */
    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            if (($payment->receivable_id === null) === ($payment->payable_id === null)) {
                throw new \InvalidArgumentException('A payment must reference exactly one receivable or payable.');
            }
        });

        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
        static::restored(fn ($model) => DashboardService::clearCache());
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
            'jenis' => PaymentJenis::class,
            'status' => SettlementStatus::class,
            'void_requested_at' => 'datetime',
            'void_reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the receivable this payment settles (for AR).
     */
    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    /**
     * Get the payable this payment settles (for AP).
     */
    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function voidRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_requested_by');
    }

    public function voidReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_reviewed_by');
    }

    /**
     * Whether this settlement is waiting for an admin decision.
     */
    public function isCancellationPending(): bool
    {
        return $this->status === SettlementStatus::PendingCancel;
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Payment #'.$this->id;
    }
}
