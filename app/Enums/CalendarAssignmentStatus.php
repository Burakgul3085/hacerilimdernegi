<?php

namespace App\Enums;

enum CalendarAssignmentStatus: string
{
    case InProgress = 'devam_ediyor';
    case Processing = 'isleme_alindi';
    case Completed = 'tamamlandi';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Devam ediyor',
            self::Processing => 'İşleme alındı',
            self::Completed => 'Tamamlandı',
        };
    }
}
