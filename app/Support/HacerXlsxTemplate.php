<?php

namespace App\Support;

/**
 * Hâcer İlim ve Kültür Topluluğu kurumsal Excel görünüm sabitleri.
 * Referans: tam genişlik birleşik başlık + kaydırmalı uzun metin (taşma yok).
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

    /** Kurum + belge türü + meta (birleşik satırlar) */
    public const BRAND_ROWS = 3;

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

    public static function metaLine(?string $context, ?string $summary): string
    {
        return trim(implode('  ·  ', array_values(array_filter([
            filled($context) ? (string) $context : null,
            filled($summary) ? (string) $summary : null,
            self::generatedAtLabel(),
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

    public static function isLongTextColumn(string $header): bool
    {
        $normalized = mb_strtolower(trim($header));

        return str_contains($normalized, 'not')
            || str_contains($normalized, 'kendinden')
            || str_contains($normalized, 'adres')
            || str_contains($normalized, 'açıklama')
            || str_contains($normalized, 'aciklama')
            || str_contains($normalized, 'kitap');
    }

    public static function columnWidth(string $header): float
    {
        $normalized = mb_strtolower(trim($header));

        return match (true) {
            str_contains($normalized, 'e-posta'), str_contains($normalized, 'email') => 30.0,
            str_contains($normalized, 'ad soyad') => 20.0,
            str_contains($normalized, 'faaliyet') => 22.0,
            str_contains($normalized, 'telefon') => 16.0,
            str_contains($normalized, 'tarih') => 18.0,
            str_contains($normalized, 'eski not'), str_contains($normalized, 'kendinden'), str_contains($normalized, 'açıklama') => 42.0,
            str_contains($normalized, 'not'), str_contains($normalized, 'kitap') => 36.0,
            str_contains($normalized, 'adres') => 28.0,
            str_contains($normalized, 'başvuru no') => 12.0,
            $normalized === 'no', $normalized === 'sayfa', $normalized === 'sıra' => 8.0,
            str_contains($normalized, 'durum') => 13.0,
            str_contains($normalized, 'kaynak'), str_contains($normalized, 'yanıt') => 14.0,
            str_contains($normalized, 'kvkk') => 9.0,
            str_contains($normalized, 'sayı'), $normalized === 'yaş', $normalized === 'yas' => 10.0,
            default => max(11.0, min(22.0, mb_strlen($header) * 1.2 + 3)),
        };
    }

    /**
     * @param  list<list<string>>  $rows
     */
    public static function columnWidthForContent(string $header, array $rows, int $columnIndex): float
    {
        $isLongText = self::isLongTextColumn($header);
        $width = max(self::columnWidth($header), mb_strlen($header) * 1.15 + 2.5);

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            $value = trim((string) ($row[$columnIndex] ?? ''));
            $length = mb_strlen($value);

            if ($length === 0) {
                continue;
            }

            if ($isLongText) {
                $width = max($width, 40.0);
            } else {
                $width = max($width, min(28.0, $length * 1.05 + 2.5));
            }
        }

        return round(min($isLongText ? 52.0 : 32.0, max(9.0, $width)), 2);
    }

    /**
     * @param  list<string>  $row
     * @param  list<float>  $columnWidths
     */
    public static function rowHeightForContent(array $row, array $columnWidths, bool $isHeader = false): float
    {
        if ($isHeader) {
            return 26.0;
        }

        $maxLines = 1;

        foreach ($row as $index => $value) {
            $text = (string) $value;

            if (trim($text) === '') {
                continue;
            }

            $maxLines = max(
                $maxLines,
                self::estimateWrappedLineCount($text, (float) ($columnWidths[$index] ?? 16.0)),
            );
        }

        // Calibri 10 varsayılan satır ≈ 12.75 pt; ekstra boşluk bırakma.
        return (float) min(409, max(18, round($maxLines * 12.75 + 1, 2)));
    }

    /**
     * Excel wrapText kelimeli metni kendi kaydırır. Boşluksuz uzun koşulara
     * sıfır genişlik boşluğu (ZWSP) eklenir; Excel bunu kaydırma noktası sayar.
     * Sert &#10; kullanılmaz — aksi halde satır yüksekliği şişer, metin üstte kalır.
     */
    public static function prepareCellTextForWrap(string $value, float $columnWidth): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        if (trim($value) === '') {
            return '';
        }

        $charsPerLine = self::charsPerLine($columnWidth);
        $zwsp = "\u{200B}";
        $pattern = '/\S{'.($charsPerLine + 1).',}/u';

        $broken = preg_replace_callback(
            $pattern,
            fn (array $match): string => implode($zwsp, mb_str_split($match[0], $charsPerLine)),
            $value,
        );

        return is_string($broken) ? $broken : $value;
    }

    public static function charsPerLine(float $columnWidth): int
    {
        // Excel sütun genişliği ≈ karakter; fazla parçalamamak için neredeyse 1:1.
        return max(10, (int) floor(max(10.0, $columnWidth)));
    }

    public static function estimateWrappedLineCount(string $text, float $columnWidth): int
    {
        $charsPerLine = self::charsPerLine($columnWidth);
        $total = 0;

        foreach (explode("\n", $text) as $paragraph) {
            if ($paragraph === '') {
                $total++;

                continue;
            }

            // ZWSP soft-wrap noktalarını satır sınırı gibi say.
            $paragraph = str_replace("\u{200B}", "\n", $paragraph);

            foreach (explode("\n", $paragraph) as $segment) {
                if ($segment === '') {
                    continue;
                }

                $total += count(self::wrapParagraph($segment, $charsPerLine));
            }
        }

        return max(1, $total);
    }

    /**
     * @return list<string>
     */
    public static function wrapParagraph(string $paragraph, int $charsPerLine): array
    {
        if (mb_strlen($paragraph) <= $charsPerLine) {
            return [$paragraph];
        }

        $words = preg_split('/\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return self::chunkUnbroken($paragraph, $charsPerLine);
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            foreach (self::chunkUnbroken($word, $charsPerLine) as $chunk) {
                if ($current === '') {
                    $current = $chunk;

                    continue;
                }

                if (mb_strlen($current) + 1 + mb_strlen($chunk) <= $charsPerLine) {
                    $current .= ' '.$chunk;

                    continue;
                }

                $lines[] = $current;
                $current = $chunk;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [$paragraph] : $lines;
    }

    /**
     * @return list<string>
     */
    private static function chunkUnbroken(string $value, int $charsPerLine): array
    {
        if (mb_strlen($value) <= $charsPerLine) {
            return [$value];
        }

        return mb_str_split($value, $charsPerLine);
    }
}
