<?php

namespace App\Models;

use App\Enums\KasStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\FundTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tanggal', 'dari_cash_account_id', 'ke_cash_account_id', 'nominal', 'keterangan', 'created_by', 'status', 'submitted_by', 'approved_by', 'approved_at', 'posted_by', 'posted_at', 'rejected_by', 'rejected_at', 'rejection_reason'])]
class FundTransfer extends Model
{
    /** @use HasFactory<FundTransferFactory> */
    use HasFactory, LogsActivity;

    /**
     * A transfer must move money between two different accounts.
     */
    protected static function booted(): void
    {
        static::saving(function (FundTransfer $transfer): void {
            if ($transfer->dari_cash_account_id === null || $transfer->ke_cash_account_id === null) {
                throw new \InvalidArgumentException('A fund transfer needs both source and destination accounts.');
            }

            if ((int) $transfer->dari_cash_account_id === (int) $transfer->ke_cash_account_id) {
                throw new \InvalidArgumentException('Source and destination accounts must be different.');
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
            'status' => KasStatus::class,
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function dariCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'dari_cash_account_id');
    }

    public function keCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'ke_cash_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Whether this transfer is posted (affects account balances).
     */
    public function isPosted(): bool
    {
        return $this->status->isPosted();
    }

    /**
     * Whether this transfer is waiting for admin approval.
     */
    public function isWaiting(): bool
    {
        return $this->status->isWaiting();
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Fund Transfer #'.$this->id;
    }
}
