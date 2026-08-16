<?php

namespace Database\Factories;

use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashAccount>
 */
class CashAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => 'R-'.fake()->unique()->numberBetween(100, 999),
            'nama' => fake()->randomElement(['Kas Kecil', 'Bank BCA', 'Bank Mandiri']),
            'jenis' => 'kas',
            'saldo_awal' => 0,
            'is_default' => false,
            'status' => 'active',
            'keterangan' => null,
        ];
    }
}
