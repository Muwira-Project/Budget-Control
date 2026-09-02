<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MasterFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['master_type_id', 'label', 'tipe', 'is_required', 'sort'])]
class MasterField extends Model
{
    /** @use HasFactory<MasterFieldFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function masterType(): BelongsTo
    {
        return $this->belongsTo(MasterType::class);
    }

    /**
     * Human-readable label of the field type.
     */
    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            'textarea' => 'Long Text',
            'number' => 'Number',
            'date' => 'Date',
            default => 'Text',
        };
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Master Field '.($this->label ?: '#'.$this->id);
    }
}
