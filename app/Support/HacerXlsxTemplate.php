<?php

namespace App\Support;

/**
 * Hâcer İlim ve Kültür Topluluğu kurumsal Excel görünüm sabitleri.
 * Tüm xlsx çıktıları bu şablonu kullanır (yazdırma görünümüyle aynı dil).
 */
class HacerXlsxTemplate
{
    public const ORGANIZATION = 'Hâcer İlim ve Kültür Topluluğu';

    public const DOCUMENT_KIND = 'Program başvuru dökümü';

    public const CREATOR = 'Hâcer İlim ve Kültür Topluluğu';

    public const COLOR_INK = 'FF2C2825';

    public const COLOR_HEADER = 'FF7A6B55';

    public const COLOR_BAND = 'FFF3EEE4';

    public const COLOR_GOLD = 'FF8A7A62';

    public const COLOR_CREAM = 'FFFBF6EC';

    public const COLOR_PAPER = 'FFFFFCF8';

    public const COLOR_LINE = 'FFE6DFD3';

    public const COLOR_GOLD_SOFT = 'FFD4CBBE';

    public const COLOR_MUTED = 'FF6B6560';

    /**
     * Marka: unvan + meta + ince altın çizgi (birleşik hücre yok).
     */
    public const BRAND_ROWS = 3;

    /** Uzun serbest metin: hücrede kalsın, yandaki sütuna taşmasın */
    public const CELL_MAX_CHARS = 72;

    public static function generatedAtLabel(): string
    {
        return now()->timezone((string) config('app.timezone'))->format('d.m.Y H:i');
    }

    public static function documentLine(): string
    {
        return self::DOCUMENT_KIND.'  ·  '.self::generatedAtLabel();
    }

    public static function footerNote(): string
    {
        return 'Anlık görüntü · Asıl kayıtlar Hâcer panelinde tutulur; indirme saklanmaz.';
    }

    /**
     * Meta satırı: faaliyet · özet (tek satır, yazdırma meta’sı gibi).
     */
    public static function metaLine(?string $context, ?string $summary): string
    {
        return trim(implode('  ·  ', array_values(array_filter([
            filled($context) ? (string) $context : null,
            self::documentLine(),
            filled($summary) ? (string) $summary : null,
        ], fn (?string $value): bool => $value !== null && $value !== ''))));
    }

    /**
     * @return list<string>
     */
    public static function identityCells(?string $context, ?string $summary): array
    {
        return array_values(array_filter([
            self::ORGANIZATION,
            filled($context) ? (string) $context : null,
            self::documentLine(),
            filled($summary) ? (string) $summary : null,
        ], fn (?string $value): bool => $value !== null && $value !== ''));
    }

    public static function brandBlockText(?string $context, ?string $summary): string
    {
        return self::metaLine($context, $summary);
    }

    public static function clipCell(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (mb_strlen($value) <= self::CELL_MAX_CHARS) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, self::CELL_MAX_CHARS - 1)).'…';
    }

    public static function columnWidth(string $header): float
    {
        $normalized = mb_strtolower(trim($header));

        return match (true) {
            str_contains($normalized, 'e-posta'), str_contains($normalized, 'email') => 32.0,
            str_contains($normalized, 'ad soyad') => 22.0,
            str_contains($normalized, 'faaliyet') => 24.0,
            str_contains($normalized, 'telefon') => 16.0,
            str_contains($normalized, 'tarih') => 18.0,
            str_contains($normalized, 'eski not'), str_contains($normalized, 'kendinden') => 30.0,
            str_contains($normalized, 'not') => 28.0,
            str_contains($normalized, 'adres') => 26.0,
            str_contains($normalized, 'başvuru no') => 12.0,
            $normalized === 'no', $normalized === 'sayfa' => 10.0,
            str_contains($normalized, 'durum') => 14.0,
            str_contains($normalized, 'kaynak'), str_contains($normalized, 'yanıt') => 15.0,
            str_contains($normalized, 'kvkk') => 10.0,
            str_contains($normalized, 'sayı'), $normalized === 'yaş', $normalized === 'yas' => 12.0,
            str_contains($normalized, 'bekleyen') => 11.0,
            default => max(12.0, min(24.0, mb_strlen($header) * 1.25 + 3)),
        };
    }

    /**
     * @param  list<list<string>>  $rows
     */
    public static function columnWidthForContent(string $header, array $rows, int $columnIndex): float
    {
        $normalized = mb_strtolower(trim($header));
        $isLongText = str_contains($normalized, 'not')
            || str_contains($normalized, 'kendinden')
            || str_contains($normalized, 'adres')
            || str_contains($normalized, 'e-posta')
            || str_contains($normalized, 'email');

        $width = max(self::columnWidth($header), mb_strlen($header) * 1.2 + 3);

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            $value = self::clipCell((string) ($row[$columnIndex] ?? ''));
            $length = mb_strlen($value);

            if ($length === 0) {
                continue;
            }

            $cap = $isLongText ? 32.0 : 28.0;
            $factor = $isLongText ? 0.85 : 1.1;
            $width = max($width, min($cap, $length * $factor + 2.5));
        }

        return round(min(34.0, max(10.0, $width)), 2);
    }
}
