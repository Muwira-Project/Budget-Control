<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Only creates the initial admin account. Dummy data for manual testing
     * is available separately via DummyDataSeeder.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@muwira.test'],
            [
                'name' => 'Admin myfinance',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ],
        );
    }
}
