<?php

namespace App\Support;

/**
 * Tüm PHPMailer bildirimleri için ortak kurumsal HTML e-posta iskeleti.
 */
final class MailTemplate
{
    /**
     * @param  array{
     *     title: string,
     *     preheader?: string|null,
     *     eyebrow?: string|null,
     *     greeting?: string|null,
     *     intro?: string|null,
     *     highlight?: string|null,
     *     body?: string|null,
     *     closing?: string|null,
     *     cta_label?: string|null,
     *     cta_url?: string|null,
     * }  $content
     */
    public static function render(array $content): string
    {
        $siteName = (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
        $tagline = (string) SiteSettings::get('tagline', '');
        $address = (string) SiteSettings::get('address', '');
        $email = (string) SiteSettings::get('email', '');
        $phone = (string) SiteSettings::get('phone', '');
        $siteUrl = self::publicBaseUrl();
        $primary = self::normalizeHex((string) SiteSettings::get('color_primary', '#161513'), '#161513');
        $gold = self::normalizeHex((string) SiteSettings::get('color_gold', '#8A7A62'), '#8A7A62');

        $contactBits = array_values(array_filter([
            $phone !== '' ? e($phone) : null,
            $email !== '' ? '<a href="mailto:'.e($email).'" style="color:#d8d0c4;text-decoration:none;">'.e($email).'</a>' : null,
        ]));

        return view('mail.brand', [
            'siteName' => $siteName,
            'tagline' => $tagline,
            'address' => $address,
            'contactLine' => implode(' · ', $contactBits),
            'siteUrl' => $siteUrl,
            'primary' => $primary,
            'gold' => $gold,
            'logoUrl' => 'cid:hacer_mail_logo',
            'socialLinks' => self::socialLinks(),
            'title' => $content['title'],
            'preheader' => $content['preheader'] ?? null,
            'eyebrow' => $content['eyebrow'] ?? null,
            'greeting' => $content['greeting'] ?? null,
            'intro' => $content['intro'] ?? null,
            'highlight' => $content['highlight'] ?? null,
            'body' => $content['body'] ?? null,
            'closing' => $content['closing'] ?? null,
            'ctaLabel' => $content['cta_label'] ?? null,
            'ctaUrl' => $content['cta_url'] ?? null,
        ])->render();
    }

    /**
     * PHPMailer’a gömülecek görseller: cid => absolute path.
     *
     * @return array<string, string>
     */
    public static function embeddedImages(): array
    {
        $embeds = [
            'hacer_mail_logo' => self::logoPath(),
        ];

        foreach (self::configuredSocialKeys() as $key) {
            $path = public_path("images/mail/{$key}.png");

            if (is_file($path)) {
                $embeds['hacer_mail_'.$key] = $path;
            }
        }

        return array_filter($embeds, 'is_file');
    }

    /**
     * @return list<array{key: string, label: string, url: string, icon: string}>
     */
    public static function socialLinks(): array
    {
        $channels = [
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'twitter' => 'X',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'facebook' => 'Facebook',
        ];

        $links = [];

        foreach ($channels as $key => $label) {
            $url = trim((string) SiteSettings::get($key, ''));

            if ($url === '') {
                continue;
            }

            $links[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
                'icon' => 'cid:hacer_mail_'.$key,
            ];
        }

        return $links;
    }

    public static function publicBaseUrl(): string
    {
        $domain = trim((string) SiteSettings::get('domain', ''));

        if ($domain !== '') {
            return 'https://'.ltrim($domain, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    public static function logoPath(): string
    {
        $configured = trim((string) SiteSettings::get('logo', ''));

        if ($configured !== '') {
            $storagePath = storage_path('app/public/'.ltrim($configured, '/'));

            if (is_file($storagePath)) {
                return $storagePath;
            }

            $publicPath = public_path(ltrim($configured, '/'));

            if (is_file($publicPath)) {
                return $publicPath;
            }
        }

        return public_path('images/mail/logo.png');
    }

    /**
     * @return list<string>
     */
    private static function configuredSocialKeys(): array
    {
        return array_values(array_filter(
            ['telegram', 'whatsapp', 'twitter', 'instagram', 'youtube', 'facebook'],
            fn (string $key): bool => filled(SiteSettings::get($key)),
        ));
    }

    private static function normalizeHex(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }
}
