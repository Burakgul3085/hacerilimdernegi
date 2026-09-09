<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Editor = 'editor';
    case Media = 'media';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Süper yönetici',
            self::Editor => 'Editör',
            self::Media => 'Medya',
        };
    }
}
