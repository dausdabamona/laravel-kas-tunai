<?php

namespace Database\Factories;

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransaksiKas>
 */
class TransaksiKasFactory extends Factory
{
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisTransaksi::cases());
        $arahDebet = $jenis->arahDefault() === 'debet';

        return [
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'kegiatan' => fake()->sentence(4),
            'penjab' => fake()->name(),
            'sumber' => fake()->randomElement(Sumber::cases()),
            'jenis' => $jenis,
            'debet' => $arahDebet ? fake()->numberBetween(100_000, 50_000_000) : 0,
            'kredit' => $arahDebet ? 0 : fake()->numberBetween(100_000, 50_000_000),
            'status_spj' => fake()->randomElement(StatusSpj::cases()),
            'parent_id' => null,
            'ref_group' => null,
        ];
    }
}
