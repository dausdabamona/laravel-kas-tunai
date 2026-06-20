<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use Illuminate\Support\Collection;

/**
 * Laporan kas: rekap, BKU (buku kas umum), LPJ. Query Eloquent langsung —
 * tanpa repository/DTO. Reuse SaldoService::saldoAwal() (rumus saldo awal
 * config tidak diduplikasi). Baris soft-deleted otomatis dikecualikan.
 */
class LaporanService
{
    public function __construct(private SaldoService $saldo) {}

    /**
     * Saldo awal periode = saldo awal config + Σ(debet−kredit) baris aktif
     * sumber tsb dengan tanggal < $dari.
     */
    public function saldoAwalPeriode(Sumber $s, string $dari): int
    {
        $delta = TransaksiKas::query()
            ->where('sumber', $s)
            ->whereDate('tanggal', '<', $dari)
            ->selectRaw('COALESCE(SUM(debet),0) - COALESCE(SUM(kredit),0) AS d')
            ->value('d');

        return $this->saldo->saldoAwal($s) + (int) $delta;
    }

    /**
     * Rekap per sumber dalam rentang.
     *
     * @return array<string, array{saldo_awal:int, debet:int, kredit:int, pengembalian:int, saldo_akhir:int}>
     */
    public function rekap(string $dari, string $sampai): array
    {
        $hasil = [];

        foreach (Sumber::cases() as $s) {
            $awal = $this->saldoAwalPeriode($s, $dari);
            $debet = (int) $this->dalamRentang($s, $dari, $sampai)->sum('debet');
            $kredit = (int) $this->dalamRentang($s, $dari, $sampai)->sum('kredit');
            $pengembalian = (int) $this->dalamRentang($s, $dari, $sampai)
                ->where('jenis', JenisTransaksi::Pengembalian)->sum('debet');

            $hasil[$s->value] = [
                'saldo_awal' => $awal,
                'debet' => $debet,
                'kredit' => $kredit,
                'pengembalian' => $pengembalian,
                'saldo_akhir' => $awal + $debet - $kredit,
            ];
        }

        return $hasil;
    }

    /**
     * Baris BKU kronologis (tanggal, no) + saldo berjalan dari saldo awal periode.
     *
     * @return Collection<int, TransaksiKas>
     */
    public function bku(Sumber $s, string $dari, string $sampai): Collection
    {
        $saldo = $this->saldoAwalPeriode($s, $dari);

        return $this->dalamRentang($s, $dari, $sampai)
            ->orderBy('tanggal')->orderBy('no')->get()
            ->map(function (TransaksiKas $t) use (&$saldo) {
                $saldo += $t->debet - $t->kredit;
                $t->saldo_berjalan = $saldo;

                return $t;
            });
    }

    private function dalamRentang(Sumber $s, string $dari, string $sampai)
    {
        return TransaksiKas::query()
            ->where('sumber', $s)
            ->whereBetween('tanggal', [$dari, $sampai]);
    }
}
