<?php

namespace App\Models;

use App\Services\CashflowService;
use Database\Factories\NonProjectExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['tanggal', 'akun_id', 'vendor_id', 'supplier_id', 'mandor_id', 'investor_id', 'nominal', 'keterangan', 'created_by'])]
class NonProjectExpense extends Model
{
    /**
     * A non-project expense may reference at most one party.
     * Every non-project expense is mirrored into Cash Activity.
     */
    protected static function booted(): void
    {
        static::saving(function (NonProjectExpense $expense): void {
            $partyCount = collect([
                $expense->vendor_id,
                $expense->supplier_id,
                $expense->mandor_id,
                $expense->investor_id,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount > 1) {
                throw new \InvalidArgumentException('A non-project expense may reference at most one party.');
            }
        });

        static::created(fn (NonProjectExpense $expense) => app(CashflowService::class)->syncFromNonProjectExpense($expense));
        static::updated(fn (NonProjectExpense $expense) => app(CashflowService::class)->syncFromNonProjectExpense($expense));
        static::deleting(function (NonProjectExpense $expense): void {
            // Hapus sebelum FK nullOnDelete mengosongkan non_project_expense_id.
            Cashflow::where('non_project_expense_id', $expense->id)->delete();
        });
    }

    /** @use HasFactory<NonProjectExpenseFactory> */
    use HasFactory;

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
     * Get the master akun for this expense.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

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
     * Get the user who created this expense.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the cash activity entry mirrored from this expense.
     */
    public function cashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class, 'non_project_expense_id');
    }

    /**
     * Display label of the party (vendor, supplier, mandor, or investor).
     */
    public function getPihakAttribute(): ?string
    {
        return $this->vendor?->nama ?? $this->supplier?->nama ?? $this->mandor?->nama ?? $this->investor?->nama;
    }

    /**
     * Display label of the party type.
     */
    public function getPihakJenisAttribute(): ?string
    {
        return match (true) {
            $this->vendor_id !== null => 'vendor',
            $this->supplier_id !== null => 'supplier',
            $this->mandor_id !== null => 'mandor',
            $this->investor_id !== null => 'investor',
            default => null,
        };
    }
}