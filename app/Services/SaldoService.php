<?php

namespace App\Services;

use App\Enums\Sumber;
use App\Models\TransaksiKas;

class SaldoService
{
    /**
     * DELTA per sumber (Rp integer): Σ(debet − kredit) baris AKTIF saja.
     *
     * Murni pergerakan kas — TANPA saldo awal. Jangan tampilkan angka ini
     * sebagai "saldo" di UI; gunakan saldoPerSumber() untuk itu.
     *
     * Query lewat model TransaksiKas agar global scope SoftDeletes aktif
     * (baris terhapus lunak otomatis dikecualikan).
     */
    public function deltaPerSumber(Sumber $sumber): int
    {
        $delta = TransaksiKas::query()
            ->where('sumber', $sumber)
            ->selectRaw('COALESCE(SUM(debet), 0) - COALESCE(SUM(kredit), 0) AS delta')
            ->value('delta');

        return (int) $delta;
    }

    /**
     * SALDO RIIL per sumber (Rp integer): saldo awal + delta.
     *
     * Inilah angka yang ditampilkan ke pengguna.
     */
    public function saldoPerSumber(Sumber $sumber): int
    {
        return $this->saldoAwal($sumber) + $this->deltaPerSumber($sumber);
    }

    /**
     * Saldo awal (pembuka) per sumber dari config.
     */
    public function saldoAwal(Sumber $sumber): int
    {
        return match ($sumber) {
            Sumber::Tunai => (int) config('kas.saldo_awal_tunai', 0),
            Sumber::Bank => (int) config('kas.saldo_awal_bank', 0),
        };
    }

    /**
     * Rekap saldo RIIL seluruh sumber + total (untuk bilah saldo di UI).
     *
     * @return array{tunai: int, bank: int, total: int}
     */
    public function rekap(): array
    {
        $tunai = $this->saldoPerSumber(Sumber::Tunai);
        $bank = $this->saldoPerSumber(Sumber::Bank);

        return [
            'tunai' => $tunai,
            'bank' => $bank,
            'total' => $tunai + $bank,
        ];
    }
}
