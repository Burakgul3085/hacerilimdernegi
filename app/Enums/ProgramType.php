<?php

namespace App\Enums;

enum ProgramType: string
{
    case Ders = 'ders';
    case Sohbet = 'sohbet';
    case KitapTahlili = 'kitap_tahlili';

    public function label(): string
    {
        return match ($this) {
            self::Ders => 'Ders',
            self::Sohbet => 'Sohbet',
            self::KitapTahlili => 'Kitap tahlili',
        };
    }
}
