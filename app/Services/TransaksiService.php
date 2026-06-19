<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiService
{
    /**
     * Pindah dana antar kas sebagai PASANGAN baris ber-ref_group sama.
     *
     * $arah: 'TUNAI_BANK' (setor ke bank) | 'BANK_TUNAI' (tarik tunai).
     * Net antar kas = nol: saldo total tidak berubah, hanya berpindah sumber.
     *
     * @return string ref_group ('TF-...')
     */
    public function pindahDana(string $arah, int $nominal, string $tanggal, ?string $keterangan = null): string
    {
        return DB::transaction(function () use ($arah, $nominal, $tanggal, $keterangan) {
            $ref = 'TF-'.Str::ulid();

            $dari = $arah === 'TUNAI_BANK' ? Sumber::Tunai : Sumber::Bank;
            $ke = $dari === Sumber::Tunai ? Sumber::Bank : Sumber::Tunai;

            $kegiatan = 'Pindah dana: '.$dari->label().' → '.$ke->label();
            $ket = $keterangan ?: $kegiatan;

            // Baris KELUAR (sumber asal).
            TransaksiKas::create([
                'tanggal' => $tanggal,
                'kegiatan' => $kegiatan,
                'keterangan' => $ket,
                'debet' => 0,
                'kredit' => $nominal,
                'sumber' => $dari,
                'jenis' => JenisTransaksi::PindahDana,
                'ref_group' => $ref,
            ]);

            // Baris MASUK (sumber tujuan).
            TransaksiKas::create([
                'tanggal' => $tanggal,
                'kegiatan' => $kegiatan,
                'keterangan' => $ket,
                'debet' => $nominal,
                'kredit' => 0,
                'sumber' => $ke,
                'jenis' => JenisTransaksi::PindahDana,
                'ref_group' => $ref,
            ]);

            return $ref;
        });
    }
}
