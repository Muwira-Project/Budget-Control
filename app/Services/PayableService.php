<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;

class PayableService
{
    /**
     * Create a payable.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payable
    {
        return Payable::create([
            'project_id' => $data['project_id'],
            'realisasi_id' => $data['realisasi_id'] ?? null,
            'akun_id' => $data['akun_id'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'mandor_id' => $data['mandor_id'] ?? null,
            'investor_id' => $data['investor_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'jenis_pajak' => $data['jenis_pajak'] ?? null,
            'pajak_include' => $data['pajak_include'] ?? true,
            'nominal_dibayar' => 0,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Update an existing payable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Payable $payable, array $data): Payable
    {
        $payable->update([
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'mandor_id' => $data['mandor_id'] ?? null,
            'investor_id' => $data['investor_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'jenis_pajak' => $data['jenis_pajak'] ?? null,
            'pajak_include' => $data['pajak_include'] ?? true,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return $payable->refresh();
    }

    /**
     * Delete a payable (payments are removed by the database cascade).
     */
    public function delete(Payable $payable): void
    {
        $payable->delete();
    }

    /**
     * List payables, optionally filtered by project and status.
     */
    public function paginate(?Project $project = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Payable::query()
            ->with(['project', 'akun', 'vendor', 'supplier', 'mandor', 'investor'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $this->applyStatusFilter($query, $status))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create or refresh the payable generated from a realisasi.
     */
    public function syncFromRealisasi(Realisasi $realisasi): ?Payable
    {
        if ($realisasi->vendor_id === null
            && $realisasi->supplier_id === null
            && $realisasi->mandor_id === null
            && $realisasi->investor_id === null) {
            return null;
        }

        $data = [
            'project_id' => $realisasi->project_id,
            'akun_id' => $realisasi->akun_id,
            'vendor_id' => $realisasi->vendor_id,
            'supplier_id' => $realisasi->supplier_id,
            'mandor_id' => $realisasi->mandor_id,
            'investor_id' => $realisasi->investor_id,
            'tanggal' => $realisasi->tanggal->format('Y-m-d'),
            'nominal' => $realisasi->nominal,
            'jenis_pajak' => null,
            'pajak_include' => true,
            'keterangan' => 'Dari realisasi #'.$realisasi->id.($realisasi->keterangan ? ': '.$realisasi->keterangan : ''),
        ];

        $payable = Payable::where('realisasi_id', $realisasi->id)->first();

        if ($payable) {
            $payable->update($data);

            return $payable->refresh();
        }

        return Payable::create($data + ['realisasi_id' => $realisasi->id]);
    }

    /**
     * Create or refresh the payable generated from an approved payment request (idempotent).
     */
    public function syncFromPaymentRequest(PaymentRequest $paymentRequest): ?Payable
    {
        $data = [
            'project_id' => $paymentRequest->project_id,
            'payment_request_id' => $paymentRequest->id,
            'akun_id' => $paymentRequest->akun_id,
            'vendor_id' => $paymentRequest->vendor_id,
            'supplier_id' => $paymentRequest->supplier_id,
            'mandor_id' => $paymentRequest->mandor_id,
            'investor_id' => $paymentRequest->investor_id,
            'tanggal' => $paymentRequest->tanggal->format('Y-m-d'),
            'jatuh_tempo' => $paymentRequest->jatuh_tempo?->format('Y-m-d'),
            'nominal' => $paymentRequest->nominal,
            'jenis_pajak' => null,
            'pajak_include' => true,
            'keterangan' => 'Dari Payment Request '.$paymentRequest->nomor,
        ];

        $payable = Payable::where('payment_request_id', $paymentRequest->id)->first();

        if ($payable) {
            $payable->update($data);

            return $payable->refresh();
        }

        return Payable::create($data);
    }

    /**
     * Remove the payable linked to a payment request (when it leaves the approved state).
     */
    public function removeForPaymentRequest(PaymentRequest $paymentRequest): void
    {
        Payable::where('payment_request_id', $paymentRequest->id)->delete();
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
