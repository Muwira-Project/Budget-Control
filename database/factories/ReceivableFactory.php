<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receivable>
 */
class ReceivableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory()->state(['status' => 'progress']),
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'jatuh_tempo' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'nominal' => fake()->numberBetween(50_000_000, 500_000_000),
            'nominal_dibayar' => 0,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
