<?php

namespace Database\Factories;

use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundTransfer>
 */
class FundTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => KasStatus::Posted,
            'dari_cash_account_id' => CashAccount::factory(),
            'ke_cash_account_id' => CashAccount::factory(),
            'nominal' => fake()->numberBetween(1_000_000, 50_000_000),
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
