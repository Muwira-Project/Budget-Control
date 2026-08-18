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
     * Update the company settings and replace the logo/illustration when new files are uploaded.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CompanySetting $settings, array $data, ?UploadedFile $logo = null, ?UploadedFile $illustration = null): CompanySetting
    {
        if ($logo) {
            $oldPath = $settings->logo_path;
            $data['logo_path'] = $logo->store('logos', 'public');

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ($illustration) {
            $oldPath = $settings->login_illustration_path;
            $data['login_illustration_path'] = $illustration->store('illustrations', 'public');

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $settings->update($data);

        return $settings->refresh();
    }
}
