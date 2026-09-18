<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\RegistrationSpreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * E-tabloyu Google E-tablolar / Excel ile açılabilen CSV olarak indirir.
 */
class ExportRegistrationSpreadsheetCsv
{
    /**
     * @param  list<array{id?: int|string, cells: array<string, string>}>|null  $gridRows  null ise kayıtlı satırlar
     */
    public function download(RegistrationSpreadsheet $spreadsheet, ?array $gridRows = null): StreamedResponse
    {
        $spreadsheet->loadMissing('rows');

        $headers = array_values($spreadsheet->headers);
        $keys = array_values($spreadsheet->column_keys);
        $rows = $gridRows ?? $spreadsheet->rows
            ->map(fn ($row): array => ['cells' => $row->cells ?? []])
            ->all();

        $filename = $this->filename($spreadsheet);

        return response()->streamDownload(function () use ($headers, $keys, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
                $line = [];

                foreach ($keys as $key) {
                    $line[] = $this->cellValue($key, $cells[$key] ?? '');
                }

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function cellValue(string $key, mixed $value): string
    {
        $text = trim((string) $value);

        if ($key !== 'status') {
            return $text;
        }

        foreach (ApplicationStatus::cases() as $status) {
            if ($status->value === $text || $status->label() === $text) {
                return $status->label();
            }
        }

        return $text;
    }

    private function filename(RegistrationSpreadsheet $spreadsheet): string
    {
        $base = preg_replace('/[^\pL\pN]+/u', '-', $spreadsheet->title) ?: 'e-tablo';
        $base = trim((string) $base, '-');

        return 'Hacer-'.$base.'-'.now()->format('Y-m-d').'.csv';
    }
}
