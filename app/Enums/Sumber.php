<?php

namespace App\Enums;

enum Sumber: string
{
    case Tunai = 'tunai';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Kas Tunai',
            self::Bank => 'Kas Bank',
        };
    }
}
