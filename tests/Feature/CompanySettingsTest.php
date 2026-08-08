<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_displays_company_settings_form(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSeeVolt('profile.company-settings-form');
    }

    public function test_company_settings_can_be_saved(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        Volt::test('profile.company-settings-form')
            ->set('companyName', 'PT Muwira Karya')
            ->set('companyAddress', 'Jl. Sudirman No. 1, Jakarta')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'PT Muwira Karya',
            'company_address' => 'Jl. Sudirman No. 1, Jakarta',
        ]);
    }

    public function test_company_logo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        Volt::test('profile.company-settings-form')
            ->set('companyName', 'PT Muwira Karya')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors();

        $settings = CompanySetting::query()->firstOrFail();

        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_company_logo_url_uses_request_relative_asset_path(): void
    {
        Storage::fake('public');

        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        Volt::test('profile.company-settings-form')
            ->set('companyName', 'PT Muwira Karya')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors();

        $settings = CompanySetting::query()->firstOrFail();

        $this->assertNotNull($settings->logo_path);
        $this->assertStringContainsString('/storage/', $settings->logo_url);
    }

    public function test_staff_cannot_see_or_update_company_settings(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/profile')->assertOk()->assertDontSeeVolt('profile.company-settings-form');

        Volt::actingAs($staff)
            ->test('profile.company-settings-form')
            ->assertForbidden();
    }
}
