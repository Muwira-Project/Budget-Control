<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->optional()->date();
        $jenis = fake()->randomElement(['barang', 'jasa']);

        return [
            'kode' => 'PRJ-'.fake()->unique()->year().'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'nama' => fake()->sentence(3),
            'lokasi' => fake()->optional()->city(),
            'jenis' => $jenis,
            'qty' => fake()->optional()->numberBetween(1, 500),
            'satuan' => fake()->optional()->randomElement(['unit', 'm2', 'lot', 'set']),
            'harga_satuan' => fake()->optional()->numberBetween(100000, 5000000),
            'pajak' => $jenis === 'jasa' ? 2.0 : 11.0,
            'tanggal_mulai' => $tanggalMulai,
            'target_selesai' => $tanggalMulai
                ? Carbon::parse($tanggalMulai)->addDays(fake()->numberBetween(30, 365))->format('Y-m-d')
                : null,
            'status' => ProjectStatus::InProgress,
        ];
    }
}
