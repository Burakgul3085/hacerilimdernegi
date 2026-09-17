<?php

namespace App\Enums;

enum BoardTier: int
{
    case Leader = 1;
    case Team = 2;

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Yönetici',
            self::Team => 'Yönetim kadrosu',
        };
    }

    public function roleLabel(): string
    {
        return match ($this) {
            self::Leader => 'Yönetici',
            self::Team => '',
        };
    }

    public function formLabel(): string
    {
        return match ($this) {
            self::Leader => 'Yönetici',
            self::Team => 'Yönetim kadrosu',
        };
    }

    /**
     * Eski başkan / yardımcı / sayman / üye kademelerini yeni iki gruba taşır.
     */
    public static function fromStored(int $value): self
    {
        return match ($value) {
            self::Leader->value => self::Leader,
            default => self::Team,
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
