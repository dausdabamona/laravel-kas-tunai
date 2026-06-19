<?php

namespace Database\Factories;

use App\Models\MultiNota;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MultiNota>
 */
class MultiNotaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_id' => TransaksiKas::factory(),
            'urutan' => 1,
            'nama_penyedia' => 'CV '.fake()->company(),
            'nominal' => fake()->numberBetween(50_000, 25_000_000),
            'npwp_penyedia' => fake()->numerify('##.###.###.#-###.000'),
            'alamat_penyedia' => fake()->address(),
            'tgl_nota' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'penyedia_id' => null,
        ];
    }
}
