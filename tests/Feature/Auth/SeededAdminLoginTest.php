<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DummyDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeededAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_seed_with_hashed_passwords_for_login(): void
    {
        $this->artisan('db:seed', ['--class' => DummyDataSeeder::class])->assertSuccessful();

        $admin = User::query()->where('email', 'admin@muwira.test')->first();
        $staff = User::query()->where('email', 'staff@muwira.test')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($staff);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(Hash::check('password', $staff->password));
        $this->assertTrue(Auth::attempt(['email' => 'admin@muwira.test', 'password' => 'password']));
        $this->assertTrue(Auth::attempt(['email' => 'staff@muwira.test', 'password' => 'password']));
    }
}
