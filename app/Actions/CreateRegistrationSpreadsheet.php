<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetRow;
use App\Models\User;
use App\Support\RegistrationExportPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Seçilen başvuruları kalıcı bir e-tablo çalışma alanına aktarır.
 */
class CreateRegistrationSpreadsheet
{
    /**
     * @param  list<int|string>|null  $registrationIds
     * @param  list<string>|null  $columnKeys
     */
    public function handle(
        User $user,
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
        ?array $registrationIds = null,
        ?array $columnKeys = null,
    ): RegistrationSpreadsheet {
        if (! Schema::hasTable('registration_spreadsheets') || ! Schema::hasTable('registration_spreadsheet_rows')) {
            throw ValidationException::withMessages([
                'registration_ids' => 'E-tablo tabloları hâlâ yok. Sunucuda sırayla: git pull && php artisan migrate --force',
            ]);
        }

        $registrationIds = $this->normalizeIds($registrationIds);
        $columnKeys = $this->normalizeKeys($columnKeys);
        $registrations = $this->registrations($activity, $status, $unassignedOnly, $registrationIds);

        if ($registrationIds !== null && $registrations->isEmpty()) {
            throw ValidationException::withMessages([
                'registration_ids' => 'En az bir başvuran seçin.',
            ]);
        }

        return DB::transaction(function () use ($user, $activity, $unassignedOnly, $columnKeys, $registrations): RegistrationSpreadsheet {
            $columns = $this->columns($activity, $unassignedOnly, $columnKeys, $registrations);
            $title = $this->title($activity, $unassignedOnly, $registrations->count());

            $spreadsheet = RegistrationSpreadsheet::query()->create([
                'user_id' => $user->getKey(),
                'activity_id' => $activity?->getKey(),
                'title' => $title,
                'source' => $unassignedOnly ? 'unassigned' : ($activity === null ? 'all' : 'activity'),
                'column_keys' => array_column($columns, 'key'),
                'headers' => array_column($columns, 'header'),
            ]);

            $sort = 0;

            foreach ($registrations as $registration) {
                RegistrationSpreadsheetRow::query()->create([
                    'registration_spreadsheet_id' => $spreadsheet->getKey(),
                    'event_registration_id' => $registration->getKey(),
                    'sort_order' => $sort++,
                    'cells' => $this->cells($registration, $columns),
                ]);
            }

            AuditLog::record(
                'created',
                RegistrationSpreadsheet::class,
                $spreadsheet->getKey(),
                $spreadsheet->title,
                ['rows' => $registrations->count()],
            );

            return $spreadsheet->load('rows');
        });
    }

    /**
     * @param  list<int>|null  $registrationIds
     * @return Collection<int, EventRegistration>
     */
    private function registrations(
        ?Activity $activity,
        ?ApplicationStatus $status,
        bool $unassignedOnly,
        ?array $registrationIds,
    ): Collection {
        return EventRegistration::query()
            ->with(['event', 'program', 'activity'])
            ->when($activity !== null, fn ($query) => $query->forActivity($activity))
            ->when($unassignedOnly, fn ($query) => $query->unassigned())
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($registrationIds !== null, function ($query) use ($registrationIds) {
                return $query->whereIn('id', $registrationIds === [] ? [0] : $registrationIds);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  list<string>|null  $columnKeys
     * @param  Collection<int, EventRegistration>  $registrations
     * @return list<array{key: string, header: string}>
     */
    private function columns(?Activity $activity, bool $unassignedOnly, ?array $columnKeys, Collection $registrations): array
    {
        if ($unassignedOnly || $activity === null) {
            return RegistrationExportPlan::plainColumns($columnKeys);
        }

        $includeLegacy = $registrations->contains(
            fn (EventRegistration $registration): bool => RegistrationExportPlan::needsLegacyNotes($registration),
        ) || ($columnKeys !== null && in_array('legacy_notes', $columnKeys, true));

        return RegistrationExportPlan::columns($activity, $includeLegacy, $columnKeys);
    }

    /**
     * @param  list<array{key: string, header: string}>  $columns
     * @return array<string, string>
     */
    private function cells(EventRegistration $registration, array $columns): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $key = $column['key'];

            if ($key === 'status') {
                $cells[$key] = $registration->status instanceof ApplicationStatus
                    ? $registration->status->value
                    : (string) $registration->status;

                continue;
            }

            $cells[$key] = RegistrationExportPlan::value($registration, $key);
        }

        return $cells;
    }

    private function title(?Activity $activity, bool $unassignedOnly, int $rowCount): string
    {
        $base = $activity?->title
            ?? ($unassignedOnly ? 'Diğer başvurular' : 'Tüm başvurular');

        $title = $base.' · '.$rowCount.' satır · '.now()->format('d.m.Y H:i');

        return Str::limit($title, 180, '');
    }

    /**
     * @param  list<int|string>|null  $registrationIds
     * @return list<int>|null
     */
    private function normalizeIds(?array $registrationIds): ?array
    {
        if ($registrationIds === null) {
            return null;
        }

        return array_values(array_filter(array_map('intval', $registrationIds)));
    }

    /**
     * @param  list<string>|array<string, mixed>|null  $columnKeys
     * @return list<string>|null
     */
    private function normalizeKeys(?array $columnKeys): ?array
    {
        if ($columnKeys === null) {
            return null;
        }

        $keys = array_is_list($columnKeys)
            ? $columnKeys
            : array_keys(array_filter($columnKeys));

        return array_values(array_filter(array_map(
            static fn (mixed $key): string => trim((string) $key),
            $keys,
        )));
    }
}
