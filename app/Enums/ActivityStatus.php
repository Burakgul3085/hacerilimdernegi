<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Ongoing = 'ongoing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Devam ediyor',
            self::Completed => 'Tamamlandı',
        };
    }
}
