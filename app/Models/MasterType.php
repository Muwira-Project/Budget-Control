<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MasterTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'deskripsi', 'flag_ar', 'flag_ap', 'aktif', 'is_system', 'sort'])]
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
            'is_system' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MasterItem::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MasterField::class)->orderBy('sort');
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Master Type '.($this->kode ?: '#'.$this->id);
    }
}
