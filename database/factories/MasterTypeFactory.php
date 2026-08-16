<?php

namespace Database\Factories;

use App\Models\MasterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterType>
 */
class MasterTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => 'MT-'.fake()->unique()->numberBetween(100, 999),
            'nama' => fake()->randomElement(['Divisi', 'Bank', 'Sumber Dana']),
            'deskripsi' => null,
            'flag_ar' => false,
            'flag_ap' => false,
            'aktif' => true,
        ];
    }
}
