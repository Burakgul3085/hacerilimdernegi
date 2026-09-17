<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Support\RegistrationExportPlan;
use App\Support\XlsxWorkbook;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Program başvurularını, faaliyet formundaki sütun seçimine göre Excel dosyasına döker.
 * Panel kayıt defteridir; dosya o anın görüntüsüdür ve sunucuda saklanmaz.
 */
class ExportEventRegistrations
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /**
     * @return array{filename: string, contents: string}
     */
    public function handle(?Activity $activity = null, ?ApplicationStatus $status = null, bool $unassignedOnly = false): array
    {
        $registrations = $this->registrations($activity, $status, $unassignedOnly);
        $sheets = [];
        $usedNames = [];
        $coverName = RegistrationExportPlan::sheetName('İçindekiler', $usedNames);
        $indexRows = [['Faaliyet', 'Başvuru', 'Bekleyen', 'Sayfa']];

        if (! $unassignedOnly) {
            $activities = $activity === null
                ? Activity::query()->ordered()->get()
                : collect([$activity]);

            foreach ($activities as $folder) {
                $rows = $registrations->get((string) $folder->getKey(), collect());
                $sheet = $this->activitySheet($folder, $rows, $usedNames);
                $sheets[] = $sheet;
                $indexRows[] = [
                    $folder->title,
                    (string) $rows->count(),
                    (string) $rows->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count(),
                    $sheet['name'],
                ];
            }
        }

        $unassigned = $registrations->get('0', collect());

        if ($unassignedOnly || ($activity === null && $unassigned->isNotEmpty())) {
            $sheet = $this->plainSheet('Diğer başvurular', $unassigned, $usedNames);
            $sheets[] = $sheet;
            $indexRows[] = [
                'Diğer başvurular',
                (string) $unassigned->count(),
                (string) $unassigned->filter(fn (EventRegistration $registration): bool => $registration->status === ApplicationStatus::Pending)->count(),
                $sheet['name'],
            ];
        }

        array_unshift($sheets, [
            'name' => $coverName,
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
            'contents' => $this->workbook->build('Hacer başvurular', $sheets),
        ];
    }

    public function download(?Activity $activity = null, ?ApplicationStatus $status = null, bool $unassignedOnly = false): StreamedResponse
    {
        $workbook = $this->handle($activity, $status, $unassignedOnly);

        return response()->streamDownload(function () use ($workbook): void {
            echo $workbook['contents'];
        }, $workbook['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return Collection<int|string, Collection<int, EventRegistration>>
     */
    private function registrations(?Activity $activity, ?ApplicationStatus $status, bool $unassignedOnly): Collection
    {
        return EventRegistration::query()
            ->with(['event', 'program', 'activity'])
            ->when($activity !== null, fn ($query) => $query->forActivity($activity))
            ->when($unassignedOnly, fn ($query) => $query->unassigned())
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (EventRegistration $registration): string => (string) ($registration->activity_id ?? 0));
    }

    /**
     * @param  Collection<int, EventRegistration>  $rows
     * @param  array<string, true>  $usedNames
     * @return array{name: string, rows: list<list<string>>}
     */
    private function activitySheet(Activity $activity, Collection $rows, array &$usedNames): array
    {
        $includeLegacy = $rows->contains(fn (EventRegistration $registration): bool => RegistrationExportPlan::needsLegacyNotes($registration));
        $columns = RegistrationExportPlan::columns($activity, $includeLegacy);

        return [
            'name' => RegistrationExportPlan::sheetName($activity->title, $usedNames),
            'rows' => $this->table($columns, $rows),
        ];
    }

    /**
     * @param  Collection<int, EventRegistration>  $rows
     * @param  array<string, true>  $usedNames
     * @return array{name: string, rows: list<list<string>>}
     */
    private function plainSheet(string $title, Collection $rows, array &$usedNames): array
    {
        $columns = [
            ['key' => 'id', 'header' => 'Başvuru no'],
            ['key' => 'submitted_at', 'header' => 'Başvuru tarihi'],
            ['key' => 'name', 'header' => 'Ad soyad'],
            ['key' => 'email', 'header' => 'E-posta'],
            ['key' => 'phone', 'header' => 'Telefon'],
            ['key' => 'status', 'header' => 'Durum'],
            ['key' => 'source', 'header' => 'Kaynak'],
            ['key' => 'legacy_notes', 'header' => 'Not'],
        ];

        return [
            'name' => RegistrationExportPlan::sheetName($title, $usedNames),
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
