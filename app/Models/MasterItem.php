<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MasterItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['master_type_id', 'kode', 'nama', 'keterangan', 'aktif'])]
class MasterItem extends Model
{
    /** @use HasFactory<MasterItemFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function masterType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class);
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Master Item '.($this->kode ?: '#'.$this->id);
    }
}
