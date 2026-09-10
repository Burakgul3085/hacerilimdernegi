<?php

namespace App\Support;

final class InstagramMedia
{
    public function __construct(
        public string $url,
        public string $shortcode,
        public string $kind,
    ) {}

    public static function tryFrom(string $url): ?self
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'], $parts['path'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
            return null;
        }

        $path = trim($parts['path'], '/');

        if (preg_match('#^(p|reel|reels|tv)/([A-Za-z0-9_-]+)#', $path, $matches) !== 1) {
            return null;
        }

        $kind = $matches[1] === 'p' ? 'post' : 'reel';
        $segment = $kind === 'post' ? 'p' : 'reel';
        $shortcode = $matches[2];

        return new self(
            url: "https://www.instagram.com/{$segment}/{$shortcode}/",
            shortcode: $shortcode,
            kind: $kind,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<self>
     */
    public static function collection(array $items): array
    {
        $media = [];

        foreach ($items as $item) {
            $parsed = self::tryFrom((string) ($item['url'] ?? ''));

            if ($parsed instanceof self) {
                $media[] = $parsed;
            }
        }

        return $media;
    }

    public static function profileUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
            return null;
        }

        $username = explode('/', trim($parts['path'] ?? '', '/'))[0] ?? '';

        if (preg_match('/^[A-Za-z0-9._]{1,30}$/', $username) !== 1) {
            return null;
        }

        if (in_array($username, ['p', 'reel', 'reels', 'tv', 'stories', 'explore'], true)) {
            return null;
        }

        return "https://www.instagram.com/{$username}/";
    }

    public function embedUrl(): string
    {
        return $this->url.'embed/captioned/';
    }

    public function previewEmbedUrl(): string
    {
        return $this->url.'embed/';
    }

    public function label(): string
    {
        return $this->kind === 'reel' ? 'Reels' : 'Gönderi';
    }
}
