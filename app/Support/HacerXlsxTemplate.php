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
        return self::DOCUMENT_KIND.'  ·  Oluşturulma: '.self::generatedAtLabel();
    }

    public static function footerNote(): string
    {
        return 'Bu dosya panel kayıtlarının anlık görüntüsüdür. Asıl kayıtlar Hâcer yönetim panelinde tutulur; indirme sunucuda saklanmaz.';
    }

    public static function columnWidth(string $header): float
    {
        $normalized = mb_strtolower(trim($header));

        return match (true) {
            str_contains($normalized, 'e-posta'), str_contains($normalized, 'email') => 30.0,
            str_contains($normalized, 'ad soyad'), str_contains($normalized, 'faaliyet') => 24.0,
            str_contains($normalized, 'telefon') => 16.0,
            str_contains($normalized, 'tarih') => 17.0,
            str_contains($normalized, 'başvuru no'), $normalized === 'no', $normalized === 'sayfa' => 12.0,
            str_contains($normalized, 'durum'), str_contains($normalized, 'kaynak'), str_contains($normalized, 'yanıt'), str_contains($normalized, 'kvkk') => 14.0,
            str_contains($normalized, 'bekleyen'), str_contains($normalized, 'başvuru') => 12.0,
            default => max(14.0, min(32.0, mb_strlen($header) * 1.35 + 4)),
        };
    }
}
