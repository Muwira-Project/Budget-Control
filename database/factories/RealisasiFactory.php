<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Kategori;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\Realisasi;
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
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );

        return [
            'project_id' => Project::factory(),
            'akun_id' => Akun::factory(),
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => MasterItem::factory()->create([
                'master_type_id' => $vendorType->id,
                'flag_ar' => false,
                'flag_ap' => true,
            ])->id,
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
            'pihak_type_id' => MasterType::firstOrCreate(
                ['kode' => 'SUPPLIER'],
                ['nama' => 'Supplier', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
            )->id,
            'pihak_item_id' => MasterItem::factory()->create([
                'master_type_id' => MasterType::where('kode', 'SUPPLIER')->firstOrFail()->id,
                'flag_ar' => false,
                'flag_ap' => true,
            ])->id,
        ]);
    }
}
