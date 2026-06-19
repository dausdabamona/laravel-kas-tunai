<?php

namespace App\Enums;

enum StatusSpj: string
{
    case Belum = 'belum';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum SPJ',
            self::Lunas => 'Lunas SPJ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Belum => 'bg-amber-100 text-amber-800',
            self::Lunas => 'bg-teal-100 text-teal-800',
        };
    }
}
