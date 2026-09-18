<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum CalendarReminderOffset: string
{
    case AtStart = 'at_start';
    case Minutes15 = '15m';
    case Hour1 = '1h';
    case Day1 = '1d';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::AtStart => 'Etkinlik saatinde',
            self::Minutes15 => '15 dakika önce',
            self::Hour1 => '1 saat önce',
            self::Day1 => '1 gün önce',
            self::Custom => 'Özel tarih/saat',
        };
    }

    public function remindAt(CarbonInterface $startsAt): ?CarbonInterface
    {
        return match ($this) {
            self::AtStart => $startsAt->copy(),
            self::Minutes15 => $startsAt->copy()->subMinutes(15),
            self::Hour1 => $startsAt->copy()->subHour(),
            self::Day1 => $startsAt->copy()->subDay(),
            self::Custom => null,
        };
    }
}
