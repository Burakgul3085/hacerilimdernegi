<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\RegistrationSpreadsheet;
use App\Support\HacerCsvTemplate;
use App\Support\HacerXlsxTemplate;
use App\Support\RegistrationExportPlan;
use App\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * E-tabloyu kurumsal Hâcer Excel şablonuyla indirir (Excel / Google E-tablolar).
 * CSV stil taşımaz; taşma ve tarih bozulmasını önlemek için xlsx kullanılır.
 */
class ExportRegistrationSpreadsheetCsv
{
    public function __construct(private XlsxWorkbook $workbook) {}

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

        $table = [$headers];

        foreach ($sourceRows as $row) {
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $line = [];

            foreach ($keys as $key) {
                $line[] = $this->displayCell($key, $cells[$key] ?? '');
            }

            $table[] = $line;
        }

        $dataCount = max(0, count($table) - 1);
        $context = $spreadsheet->activity?->title
            ?? match ($spreadsheet->source) {
                'unassigned' => 'Diğer başvurular',
                'all' => 'Tüm başvurular',
                default => 'E-tablo',
            };
        $summary = $dataCount.' satır  ·  '.count($headers).' sütun';
        $usedNames = [];
        $sheetName = RegistrationExportPlan::sheetName($context, $usedNames);
        $filename = $this->filename($spreadsheet);

        $contents = $this->workbook->build(
            HacerXlsxTemplate::ORGANIZATION.' — '.$filename,
            [[
                'name' => $sheetName,
                'context' => $context,
                'summary' => $summary,
                'rows' => $table,
            ]],
        );

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function displayCell(string $key, mixed $value): string
    {
        $text = $this->rawCellValue($key, $value);

        if ($text === '' || $text === '—') {
            return $text;
        }

        if ($key === 'submitted_at' || HacerCsvTemplate::looksLikeDateTime($text)) {
            return HacerCsvTemplate::formatDateTime($text);
        }

        return $text;
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

        return 'Hacer-'.$base.'-'.now()->format('Y-m-d').'.xlsx';
    }
}
