<?php

namespace App\Enums;

enum BoardTier: int
{
    case President = 1;
    case VicePresident = 2;
    case Officer = 3;
    case Member = 4;

    public function label(): string
    {
        return match ($this) {
            self::President => 'Başkanlık',
            self::VicePresident => 'Başkan yardımcıları',
            self::Officer => 'Yönetim kurulu',
            self::Member => 'Üyeler',
        };
    }

    public function roleLabel(): string
    {
        return match ($this) {
            self::President => 'Başkan',
            self::VicePresident => 'Başkan yardımcısı',
            self::Officer => 'Yönetim kurulu',
            self::Member => 'Üye',
        };
    }

    public function formLabel(): string
    {
        return match ($this) {
            self::President => '1 · Başkan',
            self::VicePresident => '2 · Başkan yardımcıları',
            self::Officer => '3 · Sayman, sekreter ve diğer görevler',
            self::Member => '4 · Üyeler',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function formOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tier): array => [$tier->value => $tier->formLabel()])
            ->all();
    }
}
