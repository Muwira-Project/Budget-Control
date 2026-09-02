<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor' => 'VC-'.now()->format('Y').'-'.fake()->unique()->numberBetween(1000, 9999),
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'jenis' => 'masuk',
            'cashflow_id' => null,
            'keterangan' => null,
            'created_by' => null,
        ];
    }
}
