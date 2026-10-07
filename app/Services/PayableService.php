<?php

namespace App\Services;

use App\Enums\SettlementStatus;
use App\Models\Cashflow;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableService
{
    /**
     * Guard: reject a duplicate active invoice number (case-insensitive).
     * Soft-deleted rows are ignored so a trashed record can be re-entered.
     */
    private function ensureUniqueInvoiceNumber(?string $nomorInvoice, ?int $ignoreId = null): void
    {
        if ($nomorInvoice === null || trim($nomorInvoice) === '') {
            return;
        }

        $exists = Payable::query()
            ->whereRaw('LOWER(TRIM(nomor_invoice)) = ?', [mb_strtolower(trim($nomorInvoice))])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nomor_invoice' => 'Nomor invoice sudah digunakan pada payable lain.',
            ]);
        }
    }

    /**
     * Create a payable.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payable
    {
        $this->ensureUniqueInvoiceNumber($data['nomor_invoice'] ?? null);

        $payable = Payable::create([
            'project_id' => $data['project_id'],
            'realisasi_id' => $data['realisasi_id'] ?? null,
            'akun_id' => $data['akun_id'],
            'pihak_type_id' => $data['pihak_type_id'] ?? null,
            'pihak_item_id' => $data['pihak_item_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'nomor_invoice' => $data['nomor_invoice'] ?? null,
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'jenis_pajak' => $data['jenis_pajak'] ?? null,
            'pajak_include' => $data['pajak_include'] ?? true,
            'nominal_dibayar' => 0,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        app(NotificationService::class)->notifyIfPayableOverBudget($payable);

        return $payable;
    }

    /**
     * Update an existing payable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Payable $payable, array $data): Payable
    {
        // Guard: the nominal can never drop below the amount already paid,
        // otherwise the outstanding balance (sisa) turns negative and the
        // AP aging/reporting silently corrupts.
        $newNominal = (float) ($data['nominal'] ?? $payable->nominal);

        if ($newNominal < (float) $payable->nominal_dibayar) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal cannot be lower than the amount already paid ('.number_format((float) $payable->nominal_dibayar, 0, ',', '.').').',
            ]);
        }

        $this->ensureUniqueInvoiceNumber($data['nomor_invoice'] ?? $payable->nomor_invoice, $payable->id);

        $payable->update([
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'pihak_type_id' => $data['pihak_type_id'] ?? null,
            'pihak_item_id' => $data['pihak_item_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'nomor_invoice' => $data['nomor_invoice'] ?? $payable->nomor_invoice,
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'jenis_pajak' => $data['jenis_pajak'] ?? null,
            'pajak_include' => $data['pajak_include'] ?? true,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        app(NotificationService::class)->notifyIfPayableOverBudget($payable->refresh());

        return $payable->refresh();
    }

    /**
     * Delete a payable. Any payments linked to it are removed as well so the
     * AP balance stays consistent (the FK cascade no longer applies because
     * payments use soft deletes).
     */
    public function delete(Payable $payable): void
    {
        DB::transaction(function () use ($payable): void {
            Payment::where('payable_id', $payable->id)->get()->each(fn (Payment $payment) => app(PaymentService::class)->delete($payment));

            $payable->delete();
        });
    }

    /**
     * List payables, optionally filtered by project and status.
     */
    public function paginate(?Project $project = null, ?string $status = null, ?string $aging = null, int $perPage = 10): LengthAwarePaginator
    {
        return Payable::query()
            ->with(['project', 'akun', 'pihakType', 'pihakItem'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $this->applyStatusFilter($query, $status))
            ->when($aging, fn ($query) => $this->applyAgingFilter($query, $aging))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Apply the aging bucket filter to the query (based on due date).
     *
     * Buckets: current (not due yet), 1_30, 31_60, 61_90, over_90.
     */
    protected function applyAgingFilter($query, string $aging)
    {
        $today = now()->startOfDay();

        return match ($aging) {
            'current' => $query->where(fn ($q) => $q->whereNull('jatuh_tempo')->orWhereDate('jatuh_tempo', '>=', $today)),
            '1_30' => $query->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(30))->whereDate('jatuh_tempo', '<', $today),
            '31_60' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(30))->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(60)),
            '61_90' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(60))->whereDate('jatuh_tempo', '>=', $today->copy()->subDays(90)),
            'over_90' => $query->whereDate('jatuh_tempo', '<', $today->copy()->subDays(90)),
            default => $query,
        };
    }

    /**
     * Create or refresh the payable generated from a realisasi.
     */
    public function syncFromRealisasi(Realisasi $realisasi): ?Payable
    {
        return DB::transaction(function () use ($realisasi): ?Payable {
            $payable = Payable::withTrashed()->where('realisasi_id', $realisasi->id)->first();

            if ($realisasi->pihak_type_id === null || $realisasi->pihak_item_id === null) {
                if ($payable !== null && ! $payable->trashed()) {
                    if (Payment::query()->where('payable_id', $payable->id)->exists() || (float) $payable->nominal_dibayar > 0) {
                        throw ValidationException::withMessages([
                            'pihak_item_id' => 'Remove or reverse AP settlements before removing the party from this realization.',
                        ]);
                    }

                    $payable->delete();
                }

                return null;
            }

            $data = [
                'project_id' => $realisasi->project_id,
                'akun_id' => $realisasi->akun_id,
                'pihak_type_id' => $realisasi->pihak_type_id,
                'pihak_item_id' => $realisasi->pihak_item_id,
                'tanggal' => $realisasi->tanggal->format('Y-m-d'),
                'nominal' => $realisasi->nominal,
                'jenis_pajak' => null,
                'pajak_include' => true,
                'keterangan' => 'Dari realisasi #'.$realisasi->id.($realisasi->keterangan ? ': '.$realisasi->keterangan : ''),
            ];

            $payments = $payable?->trashed()
                ? Payment::onlyTrashed()
                    ->where('payable_id', $payable->id)
                    ->whereIn('status', [SettlementStatus::Active, SettlementStatus::PendingCancel])
                    ->get()
                : collect();
            $restoredPaidAmount = (float) ($payable?->nominal_dibayar ?? 0)
                + (float) $payments->sum(fn (Payment $payment): float => (float) $payment->nominal);

            if ($payable !== null && (float) $data['nominal'] < $restoredPaidAmount) {
                throw ValidationException::withMessages([
                    'nominal' => 'The realization amount cannot be lower than the amount already paid against its AP.',
                ]);
            }

            if ($payable === null) {
                $payable = Payable::create($data + ['realisasi_id' => $realisasi->id]);
            } else {
                if ($payable->trashed()) {
                    $payable->restore();
                }

                $payable->update($data + ['nominal_dibayar' => $restoredPaidAmount]);

                foreach ($payments as $payment) {
                    Cashflow::onlyTrashed()->where('payment_id', $payment->id)->restore();
                    $payment->restore();
                    app(ActualService::class)->recordFromPayablePayment($payment);
                }
            }

            return $payable->refresh();
        });
    }

    /**
     * Apply the computed status filter to the query.
     */
    protected function applyStatusFilter($query, string $status)
    {
        return match ($status) {
            'belum_bayar' => $query->where('nominal_dibayar', 0),
            'lunas' => $query->whereColumn('nominal_dibayar', '>=', 'nominal'),
            'sebagian' => $query->where('nominal_dibayar', '>', 0)->whereColumn('nominal_dibayar', '<', 'nominal'),
            default => $query,
        };
    }
}
