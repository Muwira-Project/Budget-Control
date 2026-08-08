<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\InvestorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'telepon', 'alamat'])]
class Investor extends Model
{
    /** @use HasFactory<InvestorFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the realisasi for the investor.
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
        return 'Investor '.($this->kode ?: '#'.$this->id);
    }
}
