<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Paylaşım kartının görsel adresini ve ölçüsünü üretir.
 */
final class OpenGraphImage
{
    /**
     * @return array{url: string, width: int|null, height: int|null, mime: string|null}|null
     */
    public static function fromStoragePath(?string $path): ?array
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = rtrim(MailTemplate::publicBaseUrl(), '/').'/'.ltrim($url, '/');
        }

        [$width, $height, $mime] = self::measure(Storage::disk('public')->path($path));

        return [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
        ];
    }

    /**
     * Kapak yokken ve vitrin sayfasında kullanılan dernek logosu.
     * Üst menüdeki işaret değil, paylaşım için ayrılmış tam logo.
     *
     * @return array{url: string, width: int|null, height: int|null, mime: string|null}
     */
    public static function logo(): array
    {
        $url = asset('images/og-logo.jpg');
        [$width, $height, $mime] = self::measure(public_path('images/og-logo.jpg'));

        return [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
        ];
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: string|null}
     */
    private static function measure(string $path): array
    {
        if (! is_file($path)) {
            return [null, null, null];
        }

        $info = @getimagesize($path);

        if (! is_array($info)) {
            return [null, null, null];
        }

        $width = isset($info[0]) && $info[0] > 0 ? $info[0] : null;
        $height = isset($info[1]) && $info[1] > 0 ? $info[1] : null;
        $mime = is_string($info['mime'] ?? null) ? $info['mime'] : null;

        return [$width, $height, $mime];
    }
}
