<?php

namespace App\Support;

/**
 * Hâcer İlim ve Kültür Topluluğu kurumsal Excel görünüm sabitleri.
 * Tüm xlsx çıktıları bu şablonu kullanır.
 */
class HacerXlsxTemplate
{
    public const ORGANIZATION = 'Hâcer İlim ve Kültür Topluluğu';

    public const DOCUMENT_KIND = 'Program başvuru dökümü';

    public const CREATOR = 'Hâcer İlim ve Kültür Topluluğu';

    /** Metin mürekkebi (yumuşak koyu, saf siyah değil) */
    public const COLOR_INK = 'FF2C2825';

    /** Tablo başlığı — sıcak taupe */
    public const COLOR_HEADER = 'FF7A6B55';

    /** Marka bandı */
    public const COLOR_BAND = 'FFF3EEE4';

    /** Altın vurgu */
    public const COLOR_GOLD = 'FF8A7A62';

    /** Krem zebra */
    public const COLOR_CREAM = 'FFFBF6EC';

    /** Kâğıt */
    public const COLOR_PAPER = 'FFFFFCF8';

    /** Çizgi */
    public const COLOR_LINE = 'FFE6DFD3';

    /** Soft altın */
    public const COLOR_GOLD_SOFT = 'FFD4CBBE';

    /** Soluk metin */
    public const COLOR_MUTED = 'FF6B6560';

    /**
     * Marka: 1 kimlik satırı + 1 ince vurgu.
     * Veri başlığı hemen ardından; kaydırınca tek başlık bandı kalır.
     */
    public const BRAND_ROWS = 2;

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
     * Marka bandı metni — tek hücrede (satır kaydırmalı); sütun genişliğini şişirmez.
     */
    public static function brandBlockText(?string $context, ?string $summary): string
    {
        return implode("\n", array_values(array_filter([
            self::ORGANIZATION,
            filled($context) ? (string) $context : null,
            self::documentLine(),
            filled($summary) ? (string) $summary : null,
        ], fn (?string $value): bool => $value !== null && $value !== '')));
    }

    /**
     * @deprecated Kimlik artık tek hücrede; geriye dönük çağrılar için.
     *
     * @return list<string>
     */
    public static function identityCells(?string $context, ?string $summary): array
    {
        return array_values(array_filter([
            self::brandBlockText($context, $summary),
        ], fn (?string $value): bool => $value !== null && $value !== ''));
    }

    public static function columnWidth(string $header): float
    {
        $normalized = mb_strtolower(trim($header));

        return match (true) {
            str_contains($normalized, 'e-posta'), str_contains($normalized, 'email') => 38.0,
            str_contains($normalized, 'ad soyad') => 26.0,
            str_contains($normalized, 'faaliyet') => 28.0,
            str_contains($normalized, 'telefon') => 18.0,
            str_contains($normalized, 'tarih') => 22.0,
            str_contains($normalized, 'eski not'), str_contains($normalized, 'kendinden') => 40.0,
            str_contains($normalized, 'not') => 36.0,
            str_contains($normalized, 'adres') => 32.0,
            str_contains($normalized, 'başvuru no') => 14.0,
            $normalized === 'no', $normalized === 'sayfa' => 12.0,
            str_contains($normalized, 'durum') => 16.0,
            str_contains($normalized, 'kaynak'), str_contains($normalized, 'yanıt') => 18.0,
            str_contains($normalized, 'kvkk') => 12.0,
            str_contains($normalized, 'bekleyen') => 12.0,
            default => max(14.0, min(30.0, mb_strlen($header) * 1.4 + 4)),
        };
    }

    /**
     * Sütun genişliği: başlık (+ filtre) ve içerik sığacak kadar; uzun metinlerde wrap için makul tavan.
     *
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

        // Başlık + Excel otomatik filtre oku payı
        $width = max(self::columnWidth($header), mb_strlen($header) * 1.35 + 5.5);

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            $value = (string) ($row[$columnIndex] ?? '');
            $length = mb_strlen($value);

            if ($length === 0) {
                continue;
            }

            if ($isLongText) {
                $cap = 48.0;
                $needed = min($cap, max(30.0, min($length * 0.7 + 4, $cap)));
            } else {
                $cap = 42.0;
                $needed = min($cap, $length * 1.2 + 3.5);
            }

            $width = max($width, $needed);
        }

        return round(min(50.0, max(11.0, $width)), 2);
    }

    /**
     * Wrap için satır yüksekliği (yaklaşık satır sayısı × satır yüksekliği).
     *
     * @param  list<string>  $row
     * @param  list<float>  $columnWidths
     */
    public static function rowHeightForContent(array $row, array $columnWidths, bool $isHeader = false): float
    {
        if ($isHeader) {
            return 30.0;
        }

        $maxLines = 1;

        foreach ($row as $index => $value) {
            $text = (string) $value;
            $length = mb_strlen($text);

            if ($length === 0) {
                continue;
            }

            $colWidth = max(8.0, (float) ($columnWidths[$index] ?? 20.0));
            $charsPerLine = max(6, (int) floor($colWidth * 0.95));
            $maxLines = max($maxLines, (int) ceil($length / $charsPerLine));
        }

        return (float) min(120, max(26, $maxLines * 15 + 8));
    }
}
