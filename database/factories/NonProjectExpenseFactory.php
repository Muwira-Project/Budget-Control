<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\NonProjectExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NonProjectExpense>
 */
class NonProjectExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'akun_id' => Akun::factory(),
            'nominal' => fake()->numberBetween(100_000, 20_000_000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
