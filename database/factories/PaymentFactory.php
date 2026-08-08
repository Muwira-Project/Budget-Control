<?php

namespace Database\Factories;

use App\Enums\PaymentJenis;
use App\Models\Payment;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receivable_id' => Receivable::factory(),
            'payable_id' => null,
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'nominal' => fake()->numberBetween(1_000_000, 100_000_000),
            'jenis' => PaymentJenis::Masuk,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
