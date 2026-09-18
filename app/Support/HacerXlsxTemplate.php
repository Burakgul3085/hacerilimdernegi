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

    /** Orman / mürekkep */
    public const COLOR_FOREST = 'FF161513';

    /** Altın */
    public const COLOR_GOLD = 'FF8A7A62';

    /** Krem */
    public const COLOR_CREAM = 'FFFBF6EC';

    /** Kâğıt */
    public const COLOR_PAPER = 'FFFFFCF8';

    /** Çizgi */
    public const COLOR_LINE = 'FFE6DFD3';

    /** Soft altın */
    public const COLOR_GOLD_SOFT = 'FFD4CBBE';

    /** Soluk metin */
    public const COLOR_MUTED = 'FF6B6560';

    /** Marka satırları (veri başlığı bundan sonra) */
    public const BRAND_ROWS = 5;

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
        return 'Anlık görüntü · Asıl kayıtlar Hâcer yönetim panelinde tutulur; indirme sunucuda saklanmaz.';
    }

    public static function columnWidth(string $header): float
    {
        $normalized = mb_strtolower(trim($header));

        return match (true) {
            str_contains($normalized, 'e-posta'), str_contains($normalized, 'email') => 34.0,
            str_contains($normalized, 'ad soyad'), str_contains($normalized, 'faaliyet') => 22.0,
            str_contains($normalized, 'telefon') => 18.0,
            str_contains($normalized, 'tarih') => 18.0,
            str_contains($normalized, 'başvuru no'), $normalized === 'no', $normalized === 'sayfa' => 12.0,
            str_contains($normalized, 'durum'), str_contains($normalized, 'kaynak'), str_contains($normalized, 'yanıt'), str_contains($normalized, 'kvkk') => 14.0,
            str_contains($normalized, 'bekleyen'), str_contains($normalized, 'başvuru') => 12.0,
            default => max(12.0, min(28.0, mb_strlen($header) * 1.35 + 3)),
        };
    }

    /**
     * @param  list<list<string>>  $rows
     */
    public static function columnWidthForContent(string $header, array $rows, int $columnIndex): float
    {
        $width = self::columnWidth($header);

        foreach ($rows as $rowIndex => $row) {
            $value = (string) ($row[$columnIndex] ?? '');
            $length = mb_strlen($value);

            if ($length === 0) {
                continue;
            }

            // Başlık satırı dar kalsın; veri satırları içeriğe göre açılsın.
            $factor = $rowIndex === 0 ? 1.2 : 1.05;
            $width = max($width, min(40.0, $length * $factor + 2.5));
        }

        return round($width, 2);
    }
}
