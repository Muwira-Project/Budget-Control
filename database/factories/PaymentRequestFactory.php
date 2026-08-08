<?php

namespace Database\Factories;

use App\Enums\PaymentRequestStatus;
use App\Models\Akun;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor' => 'PR-'.fake()->unique()->numberBetween(1000, 9999),
            'project_id' => Project::factory(),
            'akun_id' => Akun::factory(),
            'vendor_id' => Vendor::factory(),
            'supplier_id' => null,
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'jatuh_tempo' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'nominal' => fake()->numberBetween(1_000_000, 100_000_000),
            'prioritas' => fake()->randomElement(['high', 'medium', 'low']),
            'status' => PaymentRequestStatus::Draft,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the payment request belongs to a supplier instead of a vendor.
     */
    public function forSupplier(): static
    {
        return $this->state(fn () => [
            'vendor_id' => null,
            'supplier_id' => Supplier::factory(),
        ]);
    }
}
