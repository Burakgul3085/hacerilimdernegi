<?php

namespace App\Support;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hâcer kurumsal CSV şablonu — tüm CSV indirmeleri bunu kullanır.
 *
 * Excel/Google: UTF-8 BOM, ; ayırıcı, marka satırları, uzun sayıların
 * bilimsel gösterime düşmesini engelleyen düz metin hücreleri.
 */
class HacerCsvTemplate
{
    public const SEPARATOR = ';';

    public const BOM = "\xEF\xBB\xBF";

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        ?string $context = null,
        ?string $summary = null,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($headers, $rows, $context, $summary): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            self::write($handle, $headers, $rows, $context, $summary);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $handle
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function write(
        $handle,
        array $headers,
        array $rows,
        ?string $context = null,
        ?string $summary = null,
    ): void {
        $headers = array_values($headers);
        $columnCount = max(1, count($headers));

        fwrite($handle, self::BOM);

        foreach (self::preambleRows($columnCount, $context, $summary) as $row) {
            fputcsv($handle, $row, self::SEPARATOR);
        }

        fputcsv($handle, $headers, self::SEPARATOR);

        foreach ($rows as $row) {
            $line = [];

            for ($index = 0; $index < $columnCount; $index++) {
                $line[] = (string) ($row[$index] ?? '');
            }

            fputcsv($handle, $line, self::SEPARATOR);
        }

        fputcsv($handle, self::padRow([''], $columnCount), self::SEPARATOR);

        foreach (self::footerRows($columnCount) as $row) {
            fputcsv($handle, $row, self::SEPARATOR);
        }
    }

    /**
     * @return list<list<string>>
     */
    public static function preambleRows(int $columnCount, ?string $context = null, ?string $summary = null): array
    {
        $rows = [
            self::padRow([HacerXlsxTemplate::ORGANIZATION], $columnCount),
            self::padRow([HacerXlsxTemplate::documentLine()], $columnCount),
        ];

        $detail = trim(implode('  ·  ', array_values(array_filter([
            filled($context) ? (string) $context : null,
            filled($summary) ? (string) $summary : null,
        ]))));

        if ($detail !== '') {
            $rows[] = self::padRow([$detail], $columnCount);
        }

        $rows[] = self::padRow([''], $columnCount);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    public static function footerRows(int $columnCount): array
    {
        return [
            self::padRow([HacerXlsxTemplate::footerNote()], $columnCount),
        ];
    }

    /**
     * Hücreyi CSV için güvenli, okunabilir metne çevirir.
     */
    public static function formatCell(?string $columnKey, ?string $header, mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '' || $text === '—') {
            return $text;
        }

        if (self::looksLikeDateTime($text)) {
            return self::forcePlainText(self::formatDateTime($text));
        }

        if (self::shouldForcePlainText($columnKey, $header, $text)) {
            return self::forcePlainText($text);
        }

        return $text;
    }

    /**
     * Excel/Google’ın değeri sayı/tarih sanmasını engeller.
     */
    public static function forcePlainText(string $value): string
    {
        return '="'.str_replace('"', '""', $value).'"';
    }

    public static function shouldForcePlainText(?string $columnKey, ?string $header, string $value): bool
    {
        $key = mb_strtolower(trim((string) $columnKey));
        $label = mb_strtolower(trim((string) $header));

        if (in_array($key, ['phone', 'telefon', 'id', 'başvuru no', 'basvuru_no'], true)) {
            return true;
        }

        if (
            str_contains($label, 'telefon')
            || str_contains($label, 'phone')
            || str_contains($label, 'başvuru no')
            || $label === 'no'
            || $label === 'sayı'
            || $label === 'sayi'
        ) {
            return true;
        }

        $digits = preg_replace('/[\s().+\-]/', '', $value) ?? '';

        if ($digits !== '' && ctype_digit($digits) && strlen($digits) >= 10) {
            return true;
        }

        if (preg_match('/^0\d+$/', $value) === 1) {
            return true;
        }

        return false;
    }

    public static function looksLikeDateTime(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $value) === 1) {
            return true;
        }

        return preg_match('/^\d{2}\.\d{2}\.\d{4}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $value) === 1;
    }

    public static function formatDateTime(string $value): string
    {
        try {
            $date = Carbon::parse($value)->timezone((string) config('app.timezone'));
        } catch (\Throwable) {
            return $value;
        }

        if ($date->secondsSinceMidnight() === 0 && ! str_contains($value, ':')) {
            return $date->format('d.m.Y');
        }

        return $date->format('d.m.Y H:i');
    }

    /**
     * @param  list<string>  $cells
     * @return list<string>
     */
    public static function padRow(array $cells, int $columnCount): array
    {
        $row = array_values($cells);

        while (count($row) < $columnCount) {
            $row[] = '';
        }

        return array_slice($row, 0, $columnCount);
    }
}
