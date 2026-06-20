<?php

namespace App\Enums;

/**
 * Status penyelesaian kas uang muka (BERBEDA dari StatusSpj).
 *
 *  - StatusSpj      : apakah bukti nota sudah menutup target belanja (nota >= target).
 *  - StatusRekonsiliasi : apakah UANG sudah beres — selisih kas antara uang muka
 *    yang diserahkan dengan nota + pengembalian + tambahan == 0.
 *
 * selisih = uang_muka + total_tambahan - total_nota - total_pengembalian
 *   selisih > 0  -> Lebih   : pelaksana wajib mengembalikan sisa.
 *   selisih < 0  -> Kurang  : bendahara wajib menambah kekurangan.
 *   selisih == 0 -> Selesai : kas nihil, pertanggungjawaban tuntas.
 */
enum StatusRekonsiliasi: string
{
    case Selesai = 'selesai';
    case Lebih = 'lebih';
    case Kurang = 'kurang';

    public static function dariSelisih(int $selisih): self
    {
        return match (true) {
            $selisih > 0 => self::Lebih,
            $selisih < 0 => self::Kurang,
            default => self::Selesai,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai (Nihil)',
            self::Lebih => 'Sisa — Wajib Dikembalikan',
            self::Kurang => 'Kurang — Bendahara Wajib Menambah',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Selesai => 'bg-teal-100 text-teal-800',
            self::Lebih => 'bg-amber-100 text-amber-800',
            self::Kurang => 'bg-red-100 text-red-800',
        };
    }
}
