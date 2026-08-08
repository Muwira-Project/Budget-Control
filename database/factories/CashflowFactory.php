<?php

namespace Database\Factories;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\Cashflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashflow>
 */
class CashflowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now'),
            'jenis' => fake()->randomElement([CashflowJenis::Masuk, CashflowJenis::Keluar]),
            'sumber' => CashflowSumber::Pendapatan,
            'payment_request_id' => null,
            'nominal' => fake()->numberBetween(1_000_000, 200_000_000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
