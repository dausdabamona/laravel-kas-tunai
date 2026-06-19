<?php

namespace App\Services;

use App\Enums\StatusSpj;
use App\Models\TransaksiKas;

class NotaService
{
    /**
     * Hitung ulang status SPJ sebuah transaksi dari rincian notanya.
     *
     * Denormalisasi MINIMAL: hanya status_spj yang dipersist (untuk filter
     * cepat). nota_total/nota_jml TIDAK disimpan — di list pakai
     * withSum('nota','nominal') & withCount('nota').
     *
     * Diset lewat updateQuietly agar TIDAK memicu audit/event di transaksi —
     * perubahan nota sudah teraudit; hindari log ganda (jaga log sunyi).
     */
    public function recalc(TransaksiKas $t): void
    {
        $notaTotal = (int) $t->nota()->sum('nominal');     // hanya nota aktif (global scope)
        $kembalian = $this->kembalianTotal($t);            // Phase 2: 0
        $target = $t->nilai_spby > 0 ? $t->nilai_spby : $t->kredit;
        $lunas = $target > 0 && ($notaTotal + $kembalian) >= $target;

        $t->updateQuietly(['status_spj' => $lunas ? StatusSpj::Lunas : StatusSpj::Belum]);
    }

    /**
     * Total pengembalian yang mengurangi beban SPJ.
     *
     * LOCK Phase 3 DITEBUS: kini membaca relasi pengembalian sungguhan
     * (sebelumnya return 0 di Phase 2). Signature recalc & pemanggil tak berubah.
     */
    private function kembalianTotal(TransaksiKas $t): int
    {
        return (int) $t->pengembalian()->sum('jumlah');
    }
}
