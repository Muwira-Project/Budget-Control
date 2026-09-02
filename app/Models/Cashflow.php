<?php

namespace App\Models;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Enums\KasStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\DashboardService;
use App\Services\VoucherService;
use Database\Factories\CashflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tanggal', 'jenis', 'sumber', 'payment_id', 'cash_account_id', 'akun_id', 'project_id', 'pihak_type_id', 'pihak_item_id', 'nominal', 'keterangan', 'status', 'submitted_by', 'approved_by', 'approved_at', 'posted_by', 'posted_at', 'rejected_by', 'rejected_at', 'rejection_reason', 'created_by'])]
class Cashflow extends Model
{
    /**
     * Keep the dashboard cache in sync and issue vouchers for posted entries.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => DashboardService::clearCache());
        static::updated(fn ($model) => DashboardService::clearCache());
        static::deleted(fn ($model) => DashboardService::clearCache());
        static::restored(fn ($model) => DashboardService::clearCache());

        static::created(function (Cashflow $cashflow): void {
            if ($cashflow->status->isPosted()) {
                app(VoucherService::class)->generateFor($cashflow);
            }
        });
    }

    /** @use HasFactory<CashflowFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

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
            'status' => KasStatus::class,
            'nominal' => 'decimal:2',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Scope for posted entries only (affect ledgers and balances).
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', KasStatus::Posted);
    }

    /**
     * Get the payment (settlement) that triggered this cashflow entry.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the project that owns this entry (direct FK for manual entries).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The project behind this entry (via the settled AP/AR record), or null
     * for manual cash entries.
     * Uses eager loaded relation if available, otherwise falls back to queries.
     */
    public function getProjectAttribute(): ?Project
    {
        // If project is already eager loaded, use it
        if ($this->relationLoaded('project')) {
            return $this->getRelation('project');
        }

        // Direct FK (manual entry with project tagging)
        if ($this->project_id !== null) {
            return $this->project()->first();
        }

        // Via payment (settlement entry)
        $payment = $this->relationLoaded('payment') ? $this->payment : $this->payment()->first();

        if ($payment === null) {
            return null;
        }

        return $payment->payable?->project ?? $payment->receivable?->project;
    }

    /**
     * Get the party master item (vendor/supplier/mandor/investor) behind this
     * entry. Checks direct FK first (manual entry), falls back to payment.
     */
    public function pihakItem()
    {
        // Direct FK (manual entry with party tagging)
        if ($this->pihak_item_id !== null) {
            return $this->belongsTo(MasterItem::class, 'pihak_item_id');
        }

        // Via payment (settlement entry)
        return $this->hasOneThrough(
            MasterItem::class,
            Payment::class,
            'id',
            'id',
            'payment_id',
            'pihak_item_id',
        );
    }

    /**
     * Get the party master type behind this entry. Checks direct FK first
     * (manual entry), falls back to payment.
     */
    public function pihakType()
    {
        // Direct FK (manual entry with party tagging)
        if ($this->pihak_type_id !== null) {
            return $this->belongsTo(MasterType::class, 'pihak_type_id');
        }

        // Via payment (settlement entry)
        return $this->hasOneThrough(
            MasterType::class,
            Payment::class,
            'id',
            'id',
            'payment_id',
            'pihak_type_id',
        );
    }

    /**
     * Display label of the party (vendor, supplier, mandor, or investor).
     * Manual cash entries and settlements without a party resolve to null.
     */
    public function getPihakAttribute(): ?string
    {
        // Direct FK (manual entry)
        if ($this->pihak_item_id !== null) {
            return $this->pihakItem?->nama;
        }

        // Via payment (settlement entry)
        $payment = $this->relationLoaded('payment') ? $this->payment : $this->payment()->first();

        if ($payment === null) {
            return null;
        }

        $party = $payment->payable?->pihakItem ?? $payment->receivable?->pihakItem;

        return $party?->nama;
    }

    /**
     * Display label of the party type: vendor, supplier, mandor, or investor.
     */
    public function getPihakJenisAttribute(): ?string
    {
        // Direct FK (manual entry)
        if ($this->pihak_type_id !== null) {
            return $this->pihakType?->kode !== null ? strtolower((string) $this->pihakType->kode) : null;
        }

        // Via payment (settlement entry)
        $payment = $this->relationLoaded('payment') ? $this->payment : $this->payment()->first();

        if ($payment === null) {
            return null;
        }

        $type = $payment->payable?->pihakType ?? $payment->receivable?->pihakType;

        return $type?->kode !== null ? strtolower((string) $type->kode) : null;
    }

    /**
     * Get the cash account (rekening) that holds this entry.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * Get the expense/revenue account (COA) linked to this entry.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Get the voucher issued for this entry.
     */
    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * Get the budget plans associated with this cashflow's project.
     */
    public function budgetPlans(): HasManyThrough
    {
        return $this->hasManyThrough(
            BudgetPlan::class,
            Project::class,
            'id',         // Foreign key on projects table
            'project_id', // Foreign key on budget_plans table
            'project_id', // Local key on cashflows table
            'id'          // Local key on projects table
        )->orderBy('periode');
    }

    /**
     * Get all budget numbers for this cashflow (comma-separated).
     */
    public function getBudgetNumbersAttribute(): array
    {
        // Use eager loaded project.budgetPlans if available
        if ($this->relationLoaded('project') && $this->project && $this->project->relationLoaded('budgetPlans')) {
            return $this->project->budgetPlans->pluck('nomor')->toArray();
        }

        // No project or budget plans not eager loaded - return empty to avoid lazy loading
        if (! $this->project_id) {
            return [];
        }

        // If project is loaded but budgetPlans not, don't lazy load
        if ($this->relationLoaded('project') && $this->project && ! $this->project->relationLoaded('budgetPlans')) {
            return [];
        }

        // Fallback to HasManyThrough (will only work if lazy loading enabled)
        return $this->budgetPlans->pluck('nomor')->toArray();
    }

    /**
     * Get primary budget number for display.
     */
    public function getBudgetNumberAttribute(): ?string
    {
        $numbers = $this->budget_numbers;

        return $numbers ? implode(', ', $numbers) : null;
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine whether this is a manually recorded entry.
     */
    public function isManual(): bool
    {
        return $this->payment_id === null;
    }

    /**
     * Whether the entry is posted and affects the ledger.
     */
    public function isPosted(): bool
    {
        return $this->status->isPosted();
    }

    /**
     * Whether the entry is waiting for admin approval.
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
        return 'Cashflow #'.$this->id;
    }
}
