<?php

namespace App\Enums;

/**
 * Komponen biaya perjalanan dinas. Transport dipecah darat & udara agar bisa
 * dirinci per moda (sesuai kebutuhan SPD). 'lainnya' menampung biaya tak baku.
 */
enum KomponenBiaya: string
{
    case UangHarian = 'uang_harian';
    case Fullboard = 'fullboard';
    case TransportDarat = 'transport_darat';
    case TransportUdara = 'transport_udara';
    case Penginapan = 'penginapan';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::UangHarian => 'Uang Harian',
            self::Fullboard => 'Uang Fullboard',
            self::TransportDarat => 'Transport Darat',
            self::TransportUdara => 'Transport Udara',
            self::Penginapan => 'Penginapan',
            self::Lainnya => 'Lainnya',
        };
    }

    /** Apakah komponen ini lazim dihitung qty × harga (mis. hari/malam). */
    public function pakaiQty(): bool
    {
        return in_array($this, [self::UangHarian, self::Fullboard, self::Penginapan], true);
    }
}
