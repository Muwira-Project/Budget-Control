<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MandorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'telepon', 'alamat'])]
class Mandor extends Model
{
    /** @use HasFactory<MandorFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the realisasi for the mandor.
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
        return 'Mandor '.($this->kode ?: '#'.$this->id);
    }
}
