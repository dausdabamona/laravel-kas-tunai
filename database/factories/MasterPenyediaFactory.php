<?php

namespace Database\Factories;

use App\Models\MasterPenyedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterPenyedia>
 */
class MasterPenyediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'CV '.fake()->unique()->company(),
            'npwp' => fake()->numerify('##.###.###.#-###.000'),
            'alamat' => fake()->address(),
            'terakhir_digunakan' => fake()->dateTimeBetween('-6 months', 'now'),
            'frekuensi' => fake()->numberBetween(1, 20),
        ];
    }
}
