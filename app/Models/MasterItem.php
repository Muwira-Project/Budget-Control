<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MasterItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['master_type_id', 'kode', 'nama', 'keterangan', 'data', 'aktif', 'flag_ar', 'flag_ap'])]
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
            'data' => 'array',
            'flag_ar' => 'boolean',
            'flag_ap' => 'boolean',
        ];
    }

    /**
     * Whether this item may be picked as an AR party (receivable).
     */
    public function isArAllowed(): bool
    {
        return $this->flag_ar === null || $this->flag_ar === true;
    }

    /**
     * Whether this item may be picked as an AP party (payable).
     */
    public function isApAllowed(): bool
    {
        return $this->flag_ap === null || $this->flag_ap === true;
    }

    public function masterType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class);
    }

    /**
     * Display label of the item (used in dropdowns).
     */
    public function getLabelAttribute(): string
    {
        return trim(($this->kode ? $this->kode.' — ' : '').$this->nama);
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Master Item '.($this->kode ?: '#'.$this->id);
    }
}
