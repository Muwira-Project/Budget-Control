<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetPlanItem>
 */
class BudgetPlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->optional()->dateTimeBetween('-1 month', 'now');

        return [
            'budget_plan_id' => BudgetPlan::factory(),
            'akun_id' => Akun::factory(),
            'nominal' => fake()->numberBetween(1_000_000, 100_000_000),
            'tanggal_mulai' => $tanggalMulai?->format('Y-m-d'),
            'tanggal_selesai' => $tanggalMulai?->modify('+14 days')->format('Y-m-d'),
        ];
    }
}
