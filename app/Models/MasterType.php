<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MasterTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'deskripsi', 'flag_ar', 'flag_ap', 'aktif'])]
class MasterType extends Model
{
    /** @use HasFactory<MasterTypeFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag_ar' => 'boolean',
            'flag_ap' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MasterItem::class);
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Master Type '.($this->kode ?: '#'.$this->id);
    }
}