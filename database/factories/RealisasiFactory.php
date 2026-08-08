<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Kategori;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Realisasi>
 */
class RealisasiFactory extends Factory
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
            'akun_id' => Akun::factory(),
            'vendor_id' => Vendor::factory(),
            'supplier_id' => null,
            'kategori_id' => Kategori::factory(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now'),
            'nominal' => fake()->numberBetween(1_000_000, 50_000_000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the realisasi belongs to a supplier (barang) instead of a vendor (jasa).
     */
    public function forSupplier(): static
    {
        return $this->state(fn () => [
            'vendor_id' => null,
            'supplier_id' => Supplier::factory(),
        ]);
    }
}
