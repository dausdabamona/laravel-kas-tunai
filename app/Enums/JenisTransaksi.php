<?php

namespace App\Enums;

enum JenisTransaksi: string
{
    case Belanja = 'belanja';
    case Masuk = 'masuk';
    case Pengembalian = 'pengembalian';
    case Tambahan = 'tambahan';
    case PindahDana = 'pindah_dana';
    case PdPokok = 'pd_pokok';
    case PdBendahara = 'pd_bendahara';
    case PdUangMuka = 'pd_uang_muka';
    case ImporBank = 'impor_bank';

    public function label(): string
    {
        return match ($this) {
            self::Belanja => 'Belanja',
            self::Masuk => 'Penerimaan / Masuk',
            self::Pengembalian => 'Pengembalian',
            self::Tambahan => 'Tambahan Kekurangan',
            self::PindahDana => 'Pindah Dana',
            self::PdPokok => 'Perjalanan Dinas (Pokok)',
            self::PdBendahara => 'Perjalanan Dinas (Bendahara)',
            self::PdUangMuka => 'Uang Muka Perjalanan Dinas',
            self::ImporBank => 'Impor Rekening Koran',
        };
    }

    public function arahDefault(): string
    {
        return match ($this) {
            self::Masuk, self::PdBendahara, self::ImporBank => 'debet',
            default => 'kredit',
        };
    }
}
