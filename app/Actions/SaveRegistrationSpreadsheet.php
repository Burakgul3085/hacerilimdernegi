<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetRow;
use Illuminate\Support\Facades\DB;

/**
 * E-tablo düzenlemelerini kaydeder; durum ve notu asıl başvuruya yazar.
 */
class SaveRegistrationSpreadsheet
{
    /**
     * @param  array{title?: string, rows?: list<array{id: int|string, cells: array<string, mixed>}>}  $data
     */
    public function handle(RegistrationSpreadsheet $spreadsheet, array $data): RegistrationSpreadsheet
    {
        return DB::transaction(function () use ($spreadsheet, $data): RegistrationSpreadsheet {
            if (array_key_exists('title', $data) && filled($data['title'])) {
                $spreadsheet->update(['title' => trim((string) $data['title'])]);
            }

            $synced = 0;

            foreach ($data['rows'] ?? [] as $rowData) {
                $row = RegistrationSpreadsheetRow::query()
                    ->where('registration_spreadsheet_id', $spreadsheet->getKey())
                    ->whereKey($rowData['id'])
                    ->first();

                if ($row === null) {
                    continue;
                }

                $cells = $this->normalizeCells($spreadsheet, is_array($rowData['cells'] ?? null) ? $rowData['cells'] : []);
                $row->update(['cells' => $cells]);

                if ($this->syncRegistration($row, $cells)) {
                    $synced++;
                }
            }

            AuditLog::record(
                'updated',
                RegistrationSpreadsheet::class,
                $spreadsheet->getKey(),
                $spreadsheet->title,
                ['synced_registrations' => $synced],
            );

            return $spreadsheet->fresh(['rows.registration']) ?? $spreadsheet;
        });
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, string>
     */
    private function normalizeCells(RegistrationSpreadsheet $spreadsheet, array $incoming): array
    {
        $cells = [];

        foreach ($spreadsheet->column_keys as $key) {
            $value = $incoming[$key] ?? '';

            if ($key === 'status') {
                $cells[$key] = $this->statusValue($value);

                continue;
            }

            $cells[$key] = trim((string) $value);
        }

        return $cells;
    }

    /**
     * @param  array<string, string>  $cells
     */
    private function syncRegistration(RegistrationSpreadsheetRow $row, array $cells): bool
    {
        if ($row->event_registration_id === null) {
            return false;
        }

        /** @var EventRegistration|null $registration */
        $registration = EventRegistration::query()->find($row->event_registration_id);

        if ($registration === null) {
            return false;
        }

        $updates = [];

        if (array_key_exists('status', $cells)) {
            $updates['status'] = ApplicationStatus::from($this->statusValue($cells['status']));
        }

        if (array_key_exists('legacy_notes', $cells)) {
            $updates['notes'] = $cells['legacy_notes'];
        }

        if (array_key_exists('notes', $cells)) {
            $updates['notes'] = $cells['notes'];
        }

        if ($updates === []) {
            return false;
        }

        $registration->update($updates);

        return true;
    }

    private function statusValue(mixed $value): string
    {
        $raw = trim((string) $value);

        foreach (ApplicationStatus::cases() as $status) {
            if ($status->value === $raw || $status->label() === $raw) {
                return $status->value;
            }
        }

        return ApplicationStatus::Pending->value;
    }
}
