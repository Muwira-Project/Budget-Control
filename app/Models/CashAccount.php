<?php

namespace App\Models;

use App\Enums\CashAccountJenis;
use App\Enums\CashAccountStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'jenis', 'saldo_awal', 'is_default', 'status', 'keterangan'])]
class CashAccount extends Model
{
    /** @use HasFactory<CashAccountFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => CashAccountJenis::class,
            'status' => CashAccountStatus::class,
            'saldo_awal' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    /**
     * The cashflow entries stored in this account.
     */
    public function cashflows(): HasMany
    {
        return $this->hasMany(Cashflow::class);
    }

    /**
     * The fund transfers out of this account.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'dari_cash_account_id');
    }

    /**
     * The fund transfers into this account.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'ke_cash_account_id');
    }

    /**
     * Current balance: saldo_awal + cash in - cash out + transfers in - transfers out.
     */
    public function getSaldoAttribute(): float
    {
        $in = (float) $this->cashflows()->where('jenis', 'masuk')->sum('nominal');
        $out = (float) $this->cashflows()->where('jenis', 'keluar')->sum('nominal');
        $trIn = (float) $this->incomingTransfers()->sum('nominal');
        $trOut = (float) $this->outgoingTransfers()->sum('nominal');

        return (float) $this->saldo_awal + $in - $out + $trIn - $trOut;
    }

    /**
     * Computed balances for every account without N+1 queries.
     *
     * @return array<int, float>
     */
    public static function balances(array $accountIds): array
    {
        if ($accountIds === []) {
            return [];
        }

        $in = static::sumByCashflow($accountIds, 'masuk');
        $out = static::sumByCashflow($accountIds, 'keluar');
        $trIn = FundTransfer::query()
            ->whereIn('ke_cash_account_id', $accountIds)
            ->groupBy('ke_cash_account_id')
            ->selectRaw('ke_cash_account_id as account_id, sum(nominal) as total')
            ->pluck('total', 'account_id')
            ->map(fn ($value) => (float) $value)
            ->all();
        $trOut = FundTransfer::query()
            ->whereIn('dari_cash_account_id', $accountIds)
            ->groupBy('dari_cash_account_id')
            ->selectRaw('dari_cash_account_id as account_id, sum(nominal) as total')
            ->pluck('total', 'account_id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $balances = [];
        foreach (CashAccount::whereIn('id', $accountIds)->get(['id', 'saldo_awal']) as $account) {
            $balances[$account->id] = (float) $account->saldo_awal
                + ($in[$account->id] ?? 0)
                - ($out[$account->id] ?? 0)
                + ($trIn[$account->id] ?? 0)
                - ($trOut[$account->id] ?? 0);
        }

        return $balances;
    }

    /**
     * The default cash account used for automatic cash entries.
     */
    public static function defaultId(): ?int
    {
        return static::query()->where('is_default', true)->where('status', 'active')->value('id');
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Cash Account '.($this->kode ?: '#'.$this->id);
    }

    /**
     * Sum cashflow nominal grouped by account for the given jenis.
     *
     * @return array<int, float>
     */
    private static function sumByCashflow(array $accountIds, string $jenis): array
    {
        return Cashflow::query()
            ->whereIn('cash_account_id', $accountIds)
            ->where('jenis', $jenis)
            ->groupBy('cash_account_id')
            ->selectRaw('cash_account_id as account_id, sum(nominal) as total')
            ->pluck('total', 'account_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }
}
