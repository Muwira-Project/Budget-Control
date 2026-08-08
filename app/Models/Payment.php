<?php

namespace App\Models;

use App\Enums\PaymentJenis;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['receivable_id', 'payable_id', 'tanggal', 'nominal', 'jenis', 'keterangan'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

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

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Payment #'.$this->id;
    }
}
