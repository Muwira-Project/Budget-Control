<?php

namespace Database\Factories;

use App\Models\MonitoringPeriod;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringPeriod>
 */
class MonitoringPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'nomor' => 'MON-'.fake()->unique()->numberBetween(1000, 9999),
            'project_id' => Project::factory(),
            'tanggal_mulai' => $mulai->format('Y-m-d'),
            'tanggal_selesai' => (clone $mulai)->modify('+13 days')->format('Y-m-d'),
        ];
    }
}
