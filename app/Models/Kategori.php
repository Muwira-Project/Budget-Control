<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama'])]
class Kategori extends Model
{
    /**
     * Keep the dashboard cache in sync with transaction data.
     */
    protected static function booted(): void
    {
        static::created(fn ($model) => \App\Services\DashboardService::clearCache());
        static::updated(fn ($model) => \App\Services\DashboardService::clearCache());
        static::deleted(fn ($model) => \App\Services\DashboardService::clearCache());
    }
    /** @use HasFactory<KategoriFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the realisasi for the kategori.
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
        return 'Category '.($this->kode ?: '#'.$this->id);
    }
}
