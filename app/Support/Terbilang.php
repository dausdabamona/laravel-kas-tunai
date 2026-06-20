<?php

namespace App\Support;

/**
 * Konversi bilangan bulat ke kata Bahasa Indonesia (untuk kuitansi).
 */
class Terbilang
{
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Ubah angka menjadi kata (tanpa "rupiah").
     */
    public static function angka(int $n): string
    {
        $n = abs($n);

        if ($n < 12) {
            return self::SATUAN[$n];
        }
        if ($n < 20) {
            return self::angka($n - 10).' belas';
        }
        if ($n < 100) {
            return self::angka(intdiv($n, 10)).' puluh'.self::sisa($n % 10);
        }
        if ($n < 200) {
            return 'seratus'.self::sisa($n % 100);
        }
        if ($n < 1_000) {
            return self::angka(intdiv($n, 100)).' ratus'.self::sisa($n % 100);
        }
        if ($n < 2_000) {
            return 'seribu'.self::sisa($n % 1_000);
        }
        if ($n < 1_000_000) {
            return self::angka(intdiv($n, 1_000)).' ribu'.self::sisa($n % 1_000);
        }
        if ($n < 1_000_000_000) {
            return self::angka(intdiv($n, 1_000_000)).' juta'.self::sisa($n % 1_000_000);
        }
        if ($n < 1_000_000_000_000) {
            return self::angka(intdiv($n, 1_000_000_000)).' miliar'.self::sisa($n % 1_000_000_000);
        }

        return self::angka(intdiv($n, 1_000_000_000_000)).' triliun'.self::sisa($n % 1_000_000_000_000);
    }

    /**
     * Ubah angka menjadi kata diakhiri " rupiah".
     */
    public static function rupiah(int $n): string
    {
        $kata = $n === 0 ? 'nol' : trim(self::angka($n));

        return $kata.' rupiah';
    }

    private static function sisa(int $n): string
    {
        return $n > 0 ? ' '.self::angka($n) : '';
    }
}
