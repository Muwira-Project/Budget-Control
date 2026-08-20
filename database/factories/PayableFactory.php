<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
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
        $vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );

        return [
            'project_id' => Project::factory(),
            'realisasi_id' => null,
            'akun_id' => Akun::factory(),
            'pihak_type_id' => $vendorType->id,
            'pihak_item_id' => MasterItem::factory()->create([
                'master_type_id' => $vendorType->id,
                'flag_ar' => false,
                'flag_ap' => true,
            ])->id,
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
