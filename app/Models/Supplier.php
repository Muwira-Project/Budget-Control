<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'telepon', 'alamat'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the realisasi for the supplier.
     */
    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Supplier '.($this->kode ?: '#'.$this->id);
    }
}
