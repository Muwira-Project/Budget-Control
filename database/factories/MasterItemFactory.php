<?php

namespace Database\Factories;

use App\Models\MasterItem;
use App\Models\MasterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterItem>
 */
class MasterItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'master_type_id' => MasterType::factory(),
            'kode' => 'MI-'.fake()->unique()->numberBetween(1000, 9999),
            'nama' => fake()->words(2, true),
            'keterangan' => null,
            'aktif' => true,
        ];
    }
}