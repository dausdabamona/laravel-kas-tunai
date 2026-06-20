<?php

namespace App\Services;

use App\Enums\StatusRekonsiliasi;
use App\Models\TransaksiKas;

/**
 * Read-model rekonsiliasi kas uang muka (tidak mempersist apa pun).
 *
 * Menghitung selisih antara uang muka yang diserahkan dengan pertanggungjawaban
 * (nota) plus penyesuaian kas (pengembalian sisa & tambahan kekurangan):
 *
 *   selisih = uang_muka + total_tambahan - total_nota - total_pengembalian
 *
 * uang_muka = `kredit` transaksi (kas yang BENAR-BENAR keluar = uang muka yang
 * diserahkan). Memakai field utama yang diisi pengguna agar perhitungan selalu
 * mengikuti nilai terbaru (tidak terkecoh field uang_diserahkan yang opsional).
 */
class RekonsiliasiService
{
    /**
     * @return array{
     *     uang_muka: int,
     *     total_nota: int,
     *     total_pengembalian: int,
     *     total_tambahan: int,
     *     selisih: int,
     *     status: StatusRekonsiliasi
     * }
     */
    public function untuk(TransaksiKas $t): array
    {
        $uangMuka = (int) $t->kredit;
        $totalNota = (int) $t->nota()->sum('nominal');
        $totalPengembalian = (int) $t->pengembalian()->sum('jumlah');
        $totalTambahan = (int) $t->tambahan()->sum('jumlah');

        $selisih = $uangMuka + $totalTambahan - $totalNota - $totalPengembalian;

        return [
            'uang_muka' => $uangMuka,
            'total_nota' => $totalNota,
            'total_pengembalian' => $totalPengembalian,
            'total_tambahan' => $totalTambahan,
            'selisih' => $selisih,
            'status' => StatusRekonsiliasi::dariSelisih($selisih),
        ];
    }
}
