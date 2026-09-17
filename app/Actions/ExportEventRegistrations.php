<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Support\HacerXlsxTemplate;
use App\Support\RegistrationExportPlan;
use App\Support\XlsxWorkbook;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Program başvurularını, indirme anındaki kişi ve sütun seçimine göre Excel dosyasına döker.
 * Panel kayıt defteridir; dosya o anın görüntüsüdür ve sunucuda saklanmaz.
 */
class ExportEventRegistrations
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /**
     * @param  list<int|string>|null  $registrationIds
     * @param  list<string>|null  $columnKeys
     * @return array{filename: string, contents: string}
     */
    public function handle(
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
        ?array $registrationIds = null,
        ?array $columnKeys = null,
    ): array {
        $registrations = $this->registrations($activity, $status, $unassignedOnly, $registrationIds);
        $sheets = [];
        $usedNames = [];
        $coverName = RegistrationExportPlan::sheetName('İçindekiler', $usedNames);
        $indexRows = [['Faaliyet', 'Başvuru', 'Bekleyen', 'Sayfa']];
        $totalRows = 0;
        $totalPending = 0;

        if (! $unassignedOnly) {
            $activities = $activity === null
                ? Activity::query()->ordered()->get()
                : collect([$activity]);

            foreach ($activities as $folder) {
                $rows = $registrations->get((string) $folder->getKey(), collect());
                $pending = $rows->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count();
                $sheet = $this->activitySheet($folder, $rows, $usedNames, $columnKeys);
                $sheets[] = $sheet;
                $totalRows += $rows->count();
                $totalPending += $pending;
                $indexRows[] = [
                    $folder->title,
                    (string) $rows->count(),
                    (string) $pending,
                    $sheet['name'],
                ];
            }
        }

        $unassigned = $registrations->get('0', collect());

        if ($unassignedOnly || ($activity === null && $unassigned->isNotEmpty())) {
            $pending = $unassigned->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count();
            $sheet = $this->plainSheet('Diğer başvurular', $unassigned, $usedNames, $columnKeys);
            $sheets[] = $sheet;
            $totalRows += $unassigned->count();
            $totalPending += $pending;
            $indexRows[] = [
                'Diğer başvurular',
                (string) $unassigned->count(),
                (string) $pending,
                $sheet['name'],
            ];
        }

        array_unshift($sheets, [
            'name' => $coverName,
            'context' => 'İçindekiler',
            'summary' => $totalRows.' başvuru  ·  '.$totalPending.' bekleyen  ·  '.(count($sheets)).' faaliyet sayfası',
            'rows' => $indexRows,
        ]);

        $filename = $activity === null
            ? 'Hacer-basvurular-'.now()->format('Y-m-d').'.xlsx'
            : 'Hacer-'.$activity->slug.'-basvurular-'.now()->format('Y-m-d').'.xlsx';

        AuditLog::record(
            'exported',
            $activity === null ? EventRegistration::class : Activity::class,
            $activity?->getKey(),
            $activity?->title ?? ($unassignedOnly ? 'Diğer başvurular' : 'Tüm başvurular'),
            ['rows' => $registrations->flatten(1)->count()],
        );

        return [
            'filename' => $filename,
            'contents' => $this->workbook->build(
                HacerXlsxTemplate::ORGANIZATION.' — '.$filename,
                $sheets,
            ),
        ];
    }

    /**
     * @param  list<int|string>|null  $registrationIds
     * @param  list<string>|null  $columnKeys
     */
    public function download(
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
        ?array $registrationIds = null,
        ?array $columnKeys = null,
    ): StreamedResponse {
        $workbook = $this->handle($activity, $status, $unassignedOnly, $registrationIds, $columnKeys);

        return response()->streamDownload(function () use ($workbook): void {
            echo $workbook['contents'];
        }, $workbook['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<int|string>|null  $registrationIds
     * @return Collection<int|string, Collection<int, EventRegistration>>
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
                $ids = array_values(array_filter(array_map('intval', $registrationIds)));

                return $query->whereIn('id', $ids === [] ? [0] : $ids);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (EventRegistration $registration): string => (string) ($registration->activity_id ?? 0));
    }

    /**
     * @param  Collection<int, EventRegistration>  $rows
     * @param  array<string, true>  $usedNames
     * @param  list<string>|null  $columnKeys
     * @return array{name: string, context: string, summary: string, rows: list<list<string>>}
     */
    private function activitySheet(Activity $activity, Collection $rows, array &$usedNames, ?array $columnKeys): array
    {
        $includeLegacy = $rows->contains(fn (EventRegistration $registration): bool => RegistrationExportPlan::needsLegacyNotes($registration))
            || ($columnKeys !== null && in_array('legacy_notes', $columnKeys, true));
        $columns = RegistrationExportPlan::columns($activity, $includeLegacy, $columnKeys);
        $pending = $rows->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count();

        return [
            'name' => RegistrationExportPlan::sheetName($activity->title, $usedNames),
            'context' => $activity->title,
            'summary' => $rows->count().' başvuru  ·  '.$pending.' bekleyen  ·  '.count($columns).' sütun',
            'rows' => $this->table($columns, $rows),
        ];
    }

    /**
     * @param  Collection<int, EventRegistration>  $rows
     * @param  array<string, true>  $usedNames
     * @param  list<string>|null  $columnKeys
     * @return array{name: string, context: string, summary: string, rows: list<list<string>>}
     */
    private function plainSheet(string $title, Collection $rows, array &$usedNames, ?array $columnKeys): array
    {
        $columns = RegistrationExportPlan::plainColumns($columnKeys);
        $pending = $rows->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count();

        return [
            'name' => RegistrationExportPlan::sheetName($title, $usedNames),
            'context' => $title,
            'summary' => $rows->count().' başvuru  ·  '.$pending.' bekleyen  ·  '.count($columns).' sütun',
            'rows' => $this->table($columns, $rows),
        ];
    }

    /**
     * @param  list<array{key: string, header: string}>  $columns
     * @param  Collection<int, EventRegistration>  $rows
     * @return list<list<string>>
     */
    private function table(array $columns, Collection $rows): array
    {
        $table = [array_column($columns, 'header')];

        foreach ($rows as $registration) {
            $table[] = RegistrationExportPlan::row($registration, $columns);
        }

        return $table;
    }
}
