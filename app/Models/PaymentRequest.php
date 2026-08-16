<?php

namespace App\Models;

use App\Enums\PaymentPriority;
use App\Enums\PaymentRequestStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PaymentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nomor', 'project_id', 'akun_id', 'vendor_id', 'supplier_id', 'mandor_id', 'investor_id', 'tanggal', 'jatuh_tempo', 'nominal', 'prioritas', 'status', 'keterangan', 'hold_reason', 'held_by', 'held_at', 'created_by', 'approved_by', 'approved_at', 'paid_at'])]
class PaymentRequest extends Model
{
    /** @use HasFactory<PaymentRequestFactory> */
    use HasFactory, LogsActivity;

    /**
     * Ensure a payment request always has one, and only one, counterparty.
     */
    protected static function booted(): void
    {
        static::saving(function (PaymentRequest $paymentRequest): void {
            $partyCount = collect([
                $paymentRequest->vendor_id,
                $paymentRequest->supplier_id,
                $paymentRequest->mandor_id,
                $paymentRequest->investor_id,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 1) {
                throw new \InvalidArgumentException('A payment request must reference exactly one party.');
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
            'prioritas' => PaymentPriority::class,
            'status' => PaymentRequestStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'held_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns the payment request.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the master akun for the payment request.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }

    /**
     * Get the vendor for the payment request.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the supplier for the payment request.
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
     * Get the user who approved this payment request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this draft.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who put this payment request on hold.
     */
    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    /**
     * Whether this payment request is currently on hold (K1: tahan pengeluaran).
     */
    public function isHeld(): bool
    {
        return $this->held_at !== null;
    }

    /**
     * Display label of the transaction party (vendor or supplier).
     */
    public function getPihakAttribute(): ?string
    {
        return $this->vendor?->nama ?? $this->supplier?->nama;
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
        return 'Payment Request '.($this->nomor ?: '#'.$this->id);
    }
}
