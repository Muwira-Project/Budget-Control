<?php

namespace App\Models;

use App\Enums\JenisAkun;
use App\Models\Concerns\LogsActivity;
use Database\Factories\AkunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode_akun', 'nama_akun', 'jenis_akun', 'kategori_id'])]
class Akun extends Model
{
    /** @use HasFactory<AkunFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_akun' => JenisAkun::class,
        ];
    }

    /**
     * Get the klasifikasi kategori for the akun.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /**
     * Get the realisasi for the akun.
     */
    public function realisasi(): HasMany
    {
        return $this->hasMany(Realisasi::class, 'akun_id');
    }

    /**
     * Get the per-project allocations for this akun.
     */
    public function projectAkuns(): HasMany
    {
        return $this->hasMany(ProjectAkun::class);
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Account '.($this->kode_akun ?: '#'.$this->id);
    }
}
