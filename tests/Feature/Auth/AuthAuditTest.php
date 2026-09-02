<?php

namespace Tests\Feature\Auth;

use App\Livewire\Actions\Logout;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_activity(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'action' => 'login',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_records_activity(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password')
            ->call('login');

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'action' => 'failed',
        ]);
    }

    public function test_logout_records_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        app(Logout::class)();

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'action' => 'logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_auth_activity_contains_ip_and_user_agent(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $activity = Activity::query()
            ->where('subject_id', $user->id)
            ->where('action', 'login')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('ip', $activity->properties);
        $this->assertArrayHasKey('user_agent', $activity->properties);
    }
}
