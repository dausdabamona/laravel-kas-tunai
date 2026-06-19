<?php

namespace App\Enums;

enum KategoriLampiran: string
{
    case FotoNota = 'foto_nota';
    case FotoBarang = 'foto_barang';
    case BuktiPd = 'bukti_pd';
    case Kuitansi = 'kuitansi';
    case Umum = 'umum';

    public function label(): string
    {
        return match ($this) {
            self::FotoNota => 'Foto Nota',
            self::FotoBarang => 'Foto Barang',
            self::BuktiPd => 'Bukti Perjalanan Dinas',
            self::Kuitansi => 'Kuitansi',
            self::Umum => 'Umum',
        };
    }
}
