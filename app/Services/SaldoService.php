<?php

namespace App\Services;

use App\Enums\Sumber;
use App\Models\TransaksiKas;

class SaldoService
{
    /**
     * Saldo bersih untuk satu sumber dana (Rp integer).
     *
     * Dihitung dari database setiap kali dipanggil — tidak disimpan ke kolom.
     */
    public function perSumber(Sumber $sumber): int
    {
        $row = TransaksiKas::query()
            ->where('sumber', $sumber)
            ->selectRaw('COALESCE(SUM(debet), 0) - COALESCE(SUM(kredit), 0) AS saldo')
            ->value('saldo');

        return (int) $row;
    }

    /**
     * Rekap saldo seluruh sumber + total.
     *
     * @return array{tunai: int, bank: int, total: int}
     */
    public function rekap(): array
    {
        $tunai = $this->perSumber(Sumber::Tunai);
        $bank = $this->perSumber(Sumber::Bank);

        return [
            'tunai' => $tunai,
            'bank' => $bank,
            'total' => $tunai + $bank,
        ];
    }
}
