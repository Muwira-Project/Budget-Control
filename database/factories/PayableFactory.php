<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Payable;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payable>
 */
class PayableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'realisasi_id' => null,
            'akun_id' => Akun::factory(),
            'vendor_id' => Vendor::factory(),
            'supplier_id' => null,
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'jatuh_tempo' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'nominal' => fake()->numberBetween(1_000_000, 100_000_000),
            'jenis_pajak' => null,
            'pajak_include' => true,
            'nominal_dibayar' => 0,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the payable belongs to a supplier instead of a vendor.
     */
    public function forSupplier(): static
    {
        return $this->state(fn () => [
            'vendor_id' => null,
            'supplier_id' => Supplier::factory(),
        ]);
    }
}
