<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\RegistrationSpreadsheet;
use App\Support\HacerCsvTemplate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * E-tabloyu ortak Hâcer CSV şablonuyla indirir (Google E-tablolar / Excel).
 */
class ExportRegistrationSpreadsheetCsv
{
    /**
     * @param  list<array{id?: int|string, cells: array<string, string>}>|null  $gridRows  null ise kayıtlı satırlar
     */
    public function download(RegistrationSpreadsheet $spreadsheet, ?array $gridRows = null): StreamedResponse
    {
        $spreadsheet->loadMissing(['rows', 'activity']);

        $headers = array_values($spreadsheet->headers);
        $keys = array_values($spreadsheet->column_keys);
        $sourceRows = $gridRows ?? $spreadsheet->rows
            ->map(fn ($row): array => ['cells' => $row->cells ?? []])
            ->all();

        $dataRows = [];

        foreach ($sourceRows as $row) {
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $line = [];

            foreach ($keys as $index => $key) {
                $header = $headers[$index] ?? null;
                $line[] = HacerCsvTemplate::formatCell(
                    $key,
                    is_string($header) ? $header : null,
                    $this->rawCellValue($key, $cells[$key] ?? ''),
                );
            }

            $dataRows[] = $line;
        }

        $summary = count($dataRows).' satır';

        return HacerCsvTemplate::download(
            $this->filename($spreadsheet),
            $headers,
            $dataRows,
            $spreadsheet->title,
            $summary,
        );
    }

    private function rawCellValue(string $key, mixed $value): string
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
