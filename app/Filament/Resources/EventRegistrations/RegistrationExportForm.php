<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Enums\ApplicationStatus;
use App\Filament\Forms\Components\ApplicantPickerTable;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Support\RegistrationExportPlan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Excel ve yazdırma modalları için ortak seçim formu.
 */
class RegistrationExportForm
{
    /**
     * @return array{status: null, registration_ids: list<string>, column_keys: list<string>}
     */
    public static function activityDefaults(Activity $activity): array
    {
        return [
            'status' => null,
            'registration_ids' => array_column(self::registrationRows(activity: $activity), 'id'),
            'column_keys' => array_keys(RegistrationExportPlan::columnOptions($activity, includeLegacyNotes: true)),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function activitySchema(int $activityId, string $peopleHelper, string $columnsLabel): array
    {
        $activity = Activity::query()->findOrFail($activityId);
        $people = self::registrationRows(activity: $activity);
        $columnOptions = RegistrationExportPlan::columnOptions($activity, includeLegacyNotes: true);

        return [
            self::statusSelect(function (mixed $state, Set $set) use ($activityId): mixed {
                return $set(
                    'registration_ids',
                    array_column(self::registrationRows(
                        activity: Activity::query()->findOrFail($activityId),
                        status: self::statusFromState($state),
                    ), 'id'),
                );
            }),
            self::peopleTable($people, $peopleHelper),
            self::columnsList($columnOptions, $columnsLabel),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function unassignedSchema(string $peopleHelper, string $columnsLabel): array
    {
        $columnOptions = RegistrationExportPlan::plainColumnOptions();
        $people = self::registrationRows(unassignedOnly: true);

        return [
            self::statusSelect(function (mixed $state, Set $set): mixed {
                return $set(
                    'registration_ids',
                    array_column(self::registrationRows(
                        status: self::statusFromState($state),
                        unassignedOnly: true,
                    ), 'id'),
                );
            }),
            self::peopleTable($people, $peopleHelper),
            self::columnsList($columnOptions, $columnsLabel),
        ];
    }

    /**
     * @return array{status: null, registration_ids: list<string>, column_keys: list<string>}
     */
    public static function unassignedDefaults(): array
    {
        $columnOptions = RegistrationExportPlan::plainColumnOptions();

        return [
            'status' => null,
            'registration_ids' => array_column(self::registrationRows(unassignedOnly: true), 'id'),
            'column_keys' => array_keys($columnOptions),
        ];
    }

    public static function statusSelect(\Closure $afterStateUpdated): Select
    {
        return Select::make('status')
            ->label('Durum')
            ->placeholder('Tümü')
            ->helperText('Değiştirince o durumdakiler işaretlenir. Tablo aynı kalır; satırları tek tek açıp kapatabilirsiniz.')
            ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                fn (ApplicationStatus $status): array => [$status->value => $status->label()],
            ))
            ->live()
            ->afterStateUpdated($afterStateUpdated);
    }

    /**
     * @param  list<array{id: string, name: string, email: string, phone: string, status: string}>  $rows
     */
    public static function peopleTable(array $rows, string $helperText): ApplicantPickerTable
    {
        return ApplicantPickerTable::make('registration_ids')
            ->label('Başvuranlar')
            ->applicants($rows)
            ->required()
            ->minItems(1)
            ->validationMessages([
                'required' => 'En az bir başvuran seçin.',
                'min' => 'En az bir başvuran seçin.',
            ])
            ->helperText($helperText);
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function columnsList(array $options, string $label): CheckboxList
    {
        return CheckboxList::make('column_keys')
            ->label($label)
            ->options($options)
            ->bulkToggleable()
            ->columns(2)
            ->required()
            ->minItems(1)
            ->validationMessages([
                'required' => 'En az bir alan seçin.',
                'min' => 'En az bir alan seçin.',
            ])
            ->helperText('Başvuru numarası her çıktıda otomatik durur.');
    }

    /**
     * @return list<array{id: string, name: string, email: string, phone: string, status: string}>
     */
    public static function registrationRows(
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
    ): array {
        /** @var Collection<int, EventRegistration> $registrations */
        $registrations = EventRegistration::query()
            ->when($activity !== null, fn (Builder $query): Builder => $query->forActivity($activity))
            ->when($unassignedOnly, fn (Builder $query): Builder => $query->unassigned())
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->orderBy('name')
            ->orderByDesc('id')
            ->get();

        return $registrations
            ->map(function (EventRegistration $registration): array {
                $status = $registration->status instanceof ApplicationStatus
                    ? $registration->status->label()
                    : (string) $registration->status;

                return [
                    'id' => (string) $registration->getKey(),
                    'name' => $registration->name,
                    'email' => $registration->email,
                    'phone' => filled($registration->phone) ? (string) $registration->phone : '—',
                    'status' => $status,
                ];
            })
            ->values()
            ->all();
    }

    public static function statusFromState(mixed $state): ?ApplicationStatus
    {
        if (! filled($state)) {
            return null;
        }

        return ApplicationStatus::from((string) $state);
    }
}
