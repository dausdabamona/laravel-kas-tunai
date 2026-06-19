<?php

namespace Database\Factories;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lampiran>
 *
 * Catatan: attachable_* TIDAK diisi di sini — disetel oleh morphMany induk
 * (mis. $transaksi->lampiran()->save(...)). path/nama_file/mime placeholder;
 * diisi sungguhan oleh LampiranService di slice 2.4.
 */
class LampiranFactory extends Factory
{
    public function definition(): array
    {
        $nama = fake()->uuid().'.jpg';

        return [
            'kategori' => fake()->randomElement(KategoriLampiran::cases()),
            'urutan' => 1,
            'disk' => 'privat',
            'path' => 'lampiran/'.$nama,
            'nama_file' => $nama,
            'mime' => 'image/jpeg',
            'meta' => [
                'lat' => fake()->latitude(),
                'lng' => fake()->longitude(),
            ],
        ];
    }
}
