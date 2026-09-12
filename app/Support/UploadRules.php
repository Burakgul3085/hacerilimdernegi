<?php

namespace App\Support;

use App\Enums\MediaType;

class UploadRules
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];

    public const AUDIO_MIMES = ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg', 'audio/x-m4a'];

    public const AUDIO_EXTENSIONS = ['mp3', 'm4a', 'wav', 'ogg'];

    public const VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
        'video/x-m4v',
    ];

    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'm4v'];

    public const PDF_MIMES = ['application/pdf'];

    public const MAX_IMAGE_KB = 204800;

    public const MAX_AUDIO_KB = 512000;

    public const MAX_VIDEO_KB = 2097152;

    public const MAX_PDF_KB = 204800;

    public const MAX_MEDIA_KB = 2097152;

    public static function maxImageKb(): int
    {
        return self::positiveKb('uploads.max_image_kb', self::MAX_IMAGE_KB);
    }

    public static function maxAudioKb(): int
    {
        return self::positiveKb('uploads.max_audio_kb', self::MAX_AUDIO_KB);
    }

    public static function maxVideoKb(): int
    {
        return self::positiveKb('uploads.max_video_kb', self::MAX_VIDEO_KB);
    }

    public static function maxPdfKb(): int
    {
        return self::positiveKb('uploads.max_pdf_kb', self::MAX_PDF_KB);
    }

    public static function maxMediaKb(): int
    {
        return max(self::maxImageKb(), self::maxAudioKb(), self::maxVideoKb());
    }

    /**
     * @return list<string>
     */
    public static function imageAndVideoMimes(): array
    {
        return [...self::IMAGE_MIMES, ...self::VIDEO_MIMES];
    }

    /**
     * @return list<string>
     */
    public static function allMediaMimes(): array
    {
        return [...self::IMAGE_MIMES, ...self::VIDEO_MIMES, ...self::AUDIO_MIMES];
    }

    public static function typeFromPath(?string $path): MediaType
    {
        if (self::isVideoPath($path)) {
            return MediaType::Video;
        }

        if (self::isAudioPath($path)) {
            return MediaType::Audio;
        }

        return MediaType::Photo;
    }

    public static function isVideoPath(?string $path): bool
    {
        return self::pathHasExtension($path, self::VIDEO_EXTENSIONS);
    }

    public static function isAudioPath(?string $path): bool
    {
        return self::pathHasExtension($path, self::AUDIO_EXTENSIONS);
    }

    public static function isImagePath(?string $path): bool
    {
        return self::pathHasExtension($path, self::IMAGE_EXTENSIONS);
    }

    public static function galleryHelperText(): string
    {
        return 'İstediğiniz kadar fotoğraf ve video ekleyin. Sıralamayı sürükleyerek değiştirin. Fotoğraf en fazla '
            .self::formatKb(self::maxImageKb())
            .', video en fazla '
            .self::formatKb(self::maxVideoKb()).'.';
    }

    public static function mediaHelperText(): string
    {
        return 'Fotoğraf, video veya ses dosyası. Tek dosya en fazla '.self::formatKb(self::maxMediaKb()).'.';
    }

    public static function formatKb(int $kb): string
    {
        if ($kb >= 1048576) {
            return self::trimDecimal($kb / 1048576).' GB';
        }

        if ($kb >= 1024) {
            return self::trimDecimal($kb / 1024).' MB';
        }

        return $kb.' KB';
    }

    /**
     * @param  list<string>  $extensions
     */
    private static function pathHasExtension(?string $path, array $extensions): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' && in_array($extension, $extensions, true);
    }

    private static function positiveKb(string $key, int $fallback): int
    {
        $value = (int) config($key, $fallback);

        return $value > 0 ? $value : $fallback;
    }

    private static function trimDecimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
