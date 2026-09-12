<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ContentMedia
{
    /**
     * @return list<string>
     */
    public static function paths(mixed $gallery): array
    {
        return collect(is_array($gallery) ? $gallery : [])
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->values()
            ->all();
    }

    /**
     * @return list<array{path: string, url: string, kind: 'image'|'video'|'audio'}>
     */
    public static function items(mixed $gallery): array
    {
        return array_map(function (string $path): array {
            return [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'kind' => self::kind($path),
            ];
        }, self::paths($gallery));
    }

    /**
     * @return 'image'|'video'|'audio'
     */
    public static function kind(string $path): string
    {
        if (UploadRules::isVideoPath($path)) {
            return 'video';
        }

        if (UploadRules::isAudioPath($path)) {
            return 'audio';
        }

        return 'image';
    }
}
