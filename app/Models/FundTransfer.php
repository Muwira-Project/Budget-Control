<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\FundTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tanggal', 'dari_cash_account_id', 'ke_cash_account_id', 'nominal', 'keterangan', 'created_by'])]
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

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Fund Transfer #'.$this->id;
    }
}