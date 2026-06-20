<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Carbon;

/**
 * Period-locking terpusat. Sumber batas: Pengaturan (editable user), dengan
 * config('kas.periode_terkunci_hingga') sebagai default awal.
 *
 * Satu titik kebenaran dipakai semua policy/service mutasi.
 */
class Periode
{
    public static function batas(): ?string
    {
        return Pengaturan::get('periode_terkunci_hingga', config('kas.periode_terkunci_hingga'));
    }

    /**
     * Apakah $tanggal (Y-m-d) berada pada periode terkunci.
     */
    public static function terkunci(string $tanggal): bool
    {
        $batas = static::batas();

        return ! empty($batas) && Carbon::parse($tanggal)->lte(Carbon::parse($batas)->endOfDay());
    }
}
