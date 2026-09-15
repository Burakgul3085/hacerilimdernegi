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

    /**
     * Yönetici maillerinde kullanılan etiket-değer tablosu.
     *
     * @param  list<array{label: string, value: string, href?: string|null}>  $rows
     */
    public static function detailRows(array $rows): string
    {
        $html = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-family:\'Segoe UI\',Arial,sans-serif;font-size:14px;color:#3a3733;">';
        $lastIndex = count($rows) - 1;

        foreach ($rows as $index => $row) {
            $padding = $index === $lastIndex ? '0' : '0 0 10px';
            $label = e($row['label']);
            $value = e($row['value']);
            $href = $row['href'] ?? null;

            $valueHtml = filled($href)
                ? '<a href="'.e((string) $href).'" style="color:#161513;text-decoration:underline;">'.$value.'</a>'
                : $value;

            $html .= <<<HTML
                <tr>
                    <td style="padding:{$padding};width:96px;vertical-align:top;color:#8a7a62;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">{$label}</td>
                    <td style="padding:{$padding};vertical-align:top;color:#161513;">{$valueHtml}</td>
                </tr>
                HTML;
        }

        return $html.'</table>';
    }

    /**
     * Dinamik form cevaplarını kurumsal kart satırları olarak basar.
     *
     * @param  list<array{label: string, value: string}>  $answers
     */
    public static function answerBlocks(array $answers, string $heading = 'Form cevapları'): string
    {
        if ($answers === []) {
            return '';
        }

        $headingHtml = e($heading);
        $html = '<p style="margin:0 0 12px;font-family:\'Segoe UI\',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#8a7a62;">'.$headingHtml.'</p>';

        foreach ($answers as $index => $answer) {
            $label = e($answer['label']);
            $value = nl2br(e($answer['value']));
            $margin = $index === array_key_last($answers) ? '0' : '0 0 12px';

            $html .= <<<HTML
                <div style="margin:{$margin};padding:12px 14px;border:1px solid #e4d9c8;border-radius:12px;background-color:#f7f1e8;">
                    <p style="margin:0 0 6px;font-family:'Segoe UI',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#8a7a62;">{$label}</p>
                    <div style="font-family:'Segoe UI',Arial,sans-serif;font-size:14px;line-height:1.7;color:#3a3733;">{$value}</div>
                </div>
                HTML;
        }

        return $html;
    }

    /**
     * @param  list<array{label: string, value: string}>  $answers
     */
    public static function answersPlainText(array $answers): string
    {
        if ($answers === []) {
            return '';
        }

        return collect($answers)
            ->map(fn (array $answer): string => $answer['label'].': '.$answer['value'])
            ->implode("\n");
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
