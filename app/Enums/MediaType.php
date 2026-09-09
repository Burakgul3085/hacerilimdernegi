<?php

namespace App\Enums;

enum MediaType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Fotoğraf',
            self::Video => 'Video',
            self::Audio => 'Ses',
        };
    }
}
