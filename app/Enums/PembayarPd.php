<?php

namespace App\Enums;

/**
 * Pihak yang membayar/menerima sebuah komponen biaya PD.
 *
 *  - Bendahara : dibayar langsung oleh bendahara ke penyedia (mis. tiket/hotel).
 *  - Pelaksana : diserahkan kepada pelaksana (mis. uang harian / lumpsum).
 *
 * Keduanya menarik dari kas institusi (tunai/bank) — lihat metode. Pembayar
 * menentukan narasi & pengelompokan pada kuitansi rampung.
 */
enum PembayarPd: string
{
    case Bendahara = 'bendahara';
    case Pelaksana = 'pelaksana';

    public function label(): string
    {
        return match ($this) {
            self::Bendahara => 'Dibayar Bendahara',
            self::Pelaksana => 'Dibayarkan ke Pelaksana',
        };
    }
}
