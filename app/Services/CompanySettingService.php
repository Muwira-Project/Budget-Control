<?php

namespace App\Services;

use App\Models\CompanySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanySettingService
{
    /**
     * Get the single company settings row, creating it when needed.
     */
    public function get(): CompanySetting
    {
        return CompanySetting::query()->first() ?? CompanySetting::create([]);
    }

    /**
     * Update the company settings and replace the logo when a new file is uploaded.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CompanySetting $settings, array $data, ?UploadedFile $logo = null): CompanySetting
    {
        if ($logo) {
            $oldPath = $settings->logo_path;
            $data['logo_path'] = $logo->store('logos', 'public');

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $settings->update($data);

        return $settings->refresh();
    }
}
