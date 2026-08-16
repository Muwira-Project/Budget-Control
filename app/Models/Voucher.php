<?php

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nomor', 'tanggal', 'jenis', 'cashflow_id', 'keterangan', 'created_by'])]
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
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
        ];
    }

    public function cashflow(): BelongsTo
    {
        return $this->belongsTo(Cashflow::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
