<?php

namespace App\Models;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use App\Services\VoucherService;
use Database\Factories\CashflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['tanggal', 'jenis', 'sumber', 'payment_request_id', 'payment_id', 'non_project_expense_id', 'cash_account_id', 'nominal', 'keterangan'])]
class Cashflow extends Model
{
    /**
     * Keep the dashboard cache in sync and issue vouchers for every cash entry.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());

        static::created(fn (Cashflow $cashflow) => app(VoucherService::class)->generateFor($cashflow));
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
     * Get the payment (settlement) that triggered this cashflow entry.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the non-project expense that triggered this cashflow entry.
     */
    public function nonProjectExpense(): BelongsTo
    {
        return $this->belongsTo(NonProjectExpense::class);
    }

    /**
     * Get the cash account (rekening) that holds this entry.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * Get the voucher issued for this entry.
     */
    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * Determine whether this is a manually recorded entry.
     */
    public function isManual(): bool
    {
        return $this->payment_request_id === null
            && $this->payment_id === null
            && $this->non_project_expense_id === null;
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Cashflow #'.$this->id;
    }
}
