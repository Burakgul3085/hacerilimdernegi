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

    /** Marka başlığı + alt bilgi için ayrılan satır sayısı (veri başlığı bundan sonra) */
    public const BRAND_ROWS = 4;

    public static function generatedAtLabel(): string
    {
        return now()->timezone((string) config('app.timezone'))->format('d.m.Y H:i');
    }

    public static function subtitleLine(?string $context = null): string
    {
        $parts = [self::DOCUMENT_KIND, 'Oluşturulma: '.self::generatedAtLabel()];

        if (filled($context)) {
            array_unshift($parts, $context);
        }

        return implode('  ·  ', $parts);
    }

    public static function footerNote(): string
    {
        return 'Bu dosya panel kayıtlarının anlık görüntüsüdür. Asıl kayıtlar Hâcer yönetim panelinde tutulur; indirme sunucuda saklanmaz.';
    }
}
