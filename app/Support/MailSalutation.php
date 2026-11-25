<?php

namespace App\Support;

/**
 * Tüm e-postalardaki hitap ("Merhaba Ad Soyad,") ve kapanış ("Saygılarımızla, Dernek") satırları.
 * Kelimeler Site ayarları → Mailer sekmesinden yönetilir; ad ve dernek adı her zaman otomatik gelir.
 */
class MailSalutation
{
    public const DEFAULT_GREETING = 'Merhaba';

    public const DEFAULT_CLOSING = 'Saygılarımızla';

    public const NAME_FULL = 'full';

    public const NAME_FIRST = 'first';

    public const MAX_LENGTH = 40;

    public static function greetingWord(): string
    {
        return self::clean((string) SiteSettings::get('mail_greeting', '')) ?: self::DEFAULT_GREETING;
    }

    public static function closingWord(): string
    {
        return self::clean((string) SiteSettings::get('mail_closing', '')) ?: self::DEFAULT_CLOSING;
    }

    public static function usesFirstNameOnly(): bool
    {
        return SiteSettings::get('mail_greeting_name', self::NAME_FULL) === self::NAME_FIRST;
    }

    /**
     * Örn. "Selamünaleyküm Burak Gül," — ad yoksa yalnızca "Selamünaleyküm,".
     */
    public static function greetingText(?string $name = null): string
    {
        return self::compose(self::greetingWord(), self::displayName($name, self::usesFirstNameOnly()));
    }

    public static function greetingHtml(?string $name = null): string
    {
        return e(self::greetingText($name));
    }

    public static function closingText(): string
    {
        return self::closingWord().",\n".self::siteName();
    }

    public static function closingHtml(): string
    {
        return e(self::closingWord()).',<br><strong>'.e(self::siteName()).'</strong>';
    }

    /**
     * Ayarlar sayfasındaki canlı örnek için kayıt beklemeden çalışır.
     */
    public static function preview(?string $greeting, ?string $closing, ?string $nameMode): string
    {
        $name = self::displayName('Burak Gül', $nameMode === self::NAME_FIRST);

        return self::compose(self::clean((string) $greeting) ?: self::DEFAULT_GREETING, $name)
            .' … '
            .(self::clean((string) $closing) ?: self::DEFAULT_CLOSING).', '.self::siteName();
    }

    /**
     * Yazılan kelimeden etiketleri, süslü parantezleri ve sondaki noktalamayı ayıklar.
     */
    public static function clean(string $word): string
    {
        $word = strip_tags($word);
        $word = str_replace(['{', '}', '<', '>'], '', $word);
        $word = trim((string) preg_replace('/\s+/u', ' ', $word));
        $word = rtrim($word, ' ,.;:!');

        return trim(mb_substr($word, 0, self::MAX_LENGTH));
    }

    /**
     * Tamamı küçük ya da büyük yazılmış adları Türkçe kurala göre düzeltir: "burak gül" → "Burak Gül".
     */
    public static function displayName(?string $name, bool $firstNameOnly = false): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $name));

        if ($name === '') {
            return '';
        }

        $words = array_map(self::titleCaseWord(...), explode(' ', $name));

        return $firstNameOnly ? $words[0] : implode(' ', $words);
    }

    private static function titleCaseWord(string $word): string
    {
        $isAllLower = $word === self::lower($word);
        $isAllUpper = $word === self::upper($word);

        if (! $isAllLower && ! $isAllUpper) {
            return $word;
        }

        $lower = self::lower($word);

        return self::upper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }

    private static function lower(string $value): string
    {
        return mb_strtolower(strtr($value, ['I' => 'ı', 'İ' => 'i']));
    }

    private static function upper(string $value): string
    {
        return mb_strtoupper(strtr($value, ['i' => 'İ', 'ı' => 'I']));
    }

    private static function compose(string $word, string $name): string
    {
        return ($name === '' ? $word : $word.' '.$name).',';
    }

    private static function siteName(): string
    {
        return (string) SiteSettings::get('site_name', 'Hâcer İlim ve Kültür Derneği');
    }
}
