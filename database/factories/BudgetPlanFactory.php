<?php

namespace Database\Factories;

use App\Models\BudgetPlan;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetPlan>
 */
class BudgetPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $estimasiPendapatan = fake()->numberBetween(100_000_000, 1_000_000_000);
        $estimasiBiaya = fake()->numberBetween(50_000_000, (int) $estimasiPendapatan);

        return [
            'project_id' => Project::factory(),
            'periode' => fake()->unique()->dateTimeBetween('-1 year', 'now')->format('Y-m'),
            'estimasi_pendapatan' => $estimasiPendapatan,
            'estimasi_biaya' => $estimasiBiaya,
            'target_laba' => max(0, $estimasiPendapatan - $estimasiBiaya),
        ];
    }
}
