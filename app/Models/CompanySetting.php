<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['company_name', 'company_address', 'logo_path'])]
class CompanySetting extends Model
{
    /**
     * Public URL of the uploaded company logo, if any.
     *
     * Uses a request-relative asset URL instead of Storage::url() so the
     * image still resolves when the app is served on a different host/port
     * than APP_URL (e.g. the Electron dev server).
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/'.ltrim($this->logo_path, '/'))
            : null;
    }
}
