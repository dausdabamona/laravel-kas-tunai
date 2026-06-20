<?php

namespace App\Services;

/**
 * Engine pajak bendahara — data-driven dari config/pajak.php.
 *
 * Murni MEMBACA & MENGHITUNG; tidak mengubah nilai config. Tarif/MAP/KJS/ambang
 * adalah DATA (config), bukan hierarki kelas — tanpa factory/strategy.
 */
class PajakService
{
    /**
     * Klasifikasikan uraian belanja ke kategori pajak via kata kunci.
     * Mengembalikan entri kategori pertama yang cocok, atau config('pajak.default').
     *
     * @return array<string, mixed>
     */
    public function klasifikasi(string $uraian): array
    {
        $s = strtolower($uraian);

        foreach (config('pajak.kategori') as $kategori) {
            foreach ($kategori['kata_kunci'] as $kunci) {
                if (str_contains($s, strtolower($kunci))) {
                    return $kategori;
                }
            }
        }

        return config('pajak.default');
    }

    /**
     * Hitung DPP, PPh, PPN untuk satu nominal terhadap kategori.
     *
     * @param  array<string, mixed>  $k  entri kategori dari config
     * @return array{kategori:string, jenis_pph:string, dpp:int, pph:int, ppn:int, map_pph:string, kjs_pph:string, manual:bool, catatan:string}
     */
    public function hitung(int $nominal, array $k, bool $punyaNpwp): array
    {
        // PPN (tarif, dengan ambang min_ppn).
        $kenaPpn = $k['ppn'] > 0 && $nominal >= $k['min_ppn'];
        $dpp = $kenaPpn ? (int) round($nominal / (1 + $k['ppn'])) : $nominal;
        $ppn = $kenaPpn ? (int) round($dpp * $k['ppn']) : 0;

        // PPh (atas DPP; ambang min_pph; gandakan non-NPWP HANYA Pasal 22 & 23; Pasal 21 manual).
        $manual = $k['jenis_pph'] === 'PPh Pasal 21'; // tarif 0 → hitung manual tabel
        $tarif = $k['tarif_pph'];
        if (! $punyaNpwp && in_array($k['jenis_pph'], ['PPh Pasal 22', 'PPh Pasal 23'], true)) {
            $tarif *= 2;
        }
        $kenaPph = $tarif > 0 && $nominal >= $k['min_pph'];
        $pph = $kenaPph ? (int) round($dpp * $tarif) : 0;

        return [
            'kategori' => $k['label'],
            'jenis_pph' => $k['jenis_pph'],
            'dpp' => $dpp,
            'pph' => $pph,
            'ppn' => $ppn,
            'map_pph' => $k['map_pph'],
            'kjs_pph' => $k['kjs_pph'],
            'manual' => $manual,
            'catatan' => $k['catatan'],
        ];
    }
}
