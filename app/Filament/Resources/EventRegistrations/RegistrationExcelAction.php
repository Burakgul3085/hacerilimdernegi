<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Filament\Forms\Components\ApplicantPickerTable;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Support\RegistrationExportPlan;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RegistrationExcelAction
{
    public static function make(string $name = 'excel', ?Activity $activity = null, bool $unassignedOnly = false): Action
    {
        if ($activity !== null) {
            return self::forActivity($name, $activity);
        }

        if ($unassignedOnly) {
            return self::forUnassigned($name);
        }

        return self::forAll($name);
    }

    public static function makeForFolderRow(string $name = 'excel'): Action
    {
        return Action::make($name)
            ->label('Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->modalHeading(fn (Activity $record): string => $record->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (Activity $record): array => self::activityDefaults($record))
            ->schema(fn (Activity $record): array => self::activitySchema((int) $record->getKey()))
            ->action(function (array $data, Activity $record, ExportEventRegistrations $export) {
                return self::downloadActivity($data, $record, $export);
            });
    }

    private static function forActivity(string $name, Activity $activity): Action
    {
        $activityId = (int) $activity->getKey();

        return Action::make($name)
            ->label('Excel\'e aktar')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading($activity->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez. Başvuru numarası her dosyada durur.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => self::activityDefaults(Activity::query()->findOrFail($activityId)))
            ->schema(fn (): array => self::activitySchema($activityId))
            ->action(function (array $data, ExportEventRegistrations $export) use ($activityId) {
                return self::downloadActivity($data, Activity::query()->findOrFail($activityId), $export);
            });
    }

    private static function forUnassigned(string $name): Action
    {
        $columnOptions = RegistrationExportPlan::plainColumnOptions();
        $people = self::registrationRows(unassignedOnly: true);

        return Action::make($name)
            ->label('Excel indir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Diğer başvurular')
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => [
                'status' => null,
                'registration_ids' => array_column(self::registrationRows(unassignedOnly: true), 'id'),
                'column_keys' => array_keys($columnOptions),
            ])
            ->schema([
                self::statusSelect(function (mixed $state, Set $set): mixed {
                    return $set(
                        'registration_ids',
                        array_column(self::registrationRows(
                            status: self::statusFromState($state),
                            unassignedOnly: true,
                        ), 'id'),
                    );
                }),
                self::peopleTable($people),
                self::columnsList($columnOptions),
            ])
            ->action(function (array $data, ExportEventRegistrations $export) {
                return $export->download(
                    unassignedOnly: true,
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                );
            });
    }

    private static function forAll(string $name): Action
    {
        return Action::make($name)
            ->label('Excel indir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Excel indir')
            ->modalDescription('Tüm faaliyetler tek dosyada iner. Alanları daraltmak için ilgili faaliyet klasöründen aktarın.')
            ->modalSubmitActionLabel('İndir')
            ->schema([
                Select::make('status')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                        fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->action(function (array $data, ExportEventRegistrations $export) {
                $status = filled($data['status'] ?? null)
                    ? ApplicationStatus::from($data['status'])
                    : null;

                return $export->download(status: $status);
            });
    }

    /**
     * @return array{status: null, registration_ids: list<string>, column_keys: list<string>}
     */
    private static function activityDefaults(Activity $activity): array
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
    private static function activitySchema(int $activityId): array
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
            self::peopleTable($people),
            self::columnsList($columnOptions),
        ];
    }

    private static function statusSelect(\Closure $afterStateUpdated): Select
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
    private static function peopleTable(array $rows): ApplicantPickerTable
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
            ->helperText('Kurumsal Excel’e yalnızca işaretli satırlar yazılır.');
    }

    /**
     * @param  array<string, string>  $options
     */
    private static function columnsList(array $options): CheckboxList
    {
        return CheckboxList::make('column_keys')
            ->label('Excel alanları')
            ->options($options)
            ->bulkToggleable()
            ->columns(2)
            ->required()
            ->minItems(1)
            ->validationMessages([
                'required' => 'En az bir alan seçin.',
                'min' => 'En az bir alan seçin.',
            ])
            ->helperText('Başvuru numarası her dosyada otomatik durur.');
    }

    /**
     * @return list<array{id: string, name: string, email: string, phone: string, status: string}>
     */
    private static function registrationRows(
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

    private static function statusFromState(mixed $state): ?ApplicationStatus
    {
        if (! filled($state)) {
            return null;
        }

        return ApplicationStatus::from((string) $state);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function downloadActivity(array $data, Activity $activity, ExportEventRegistrations $export): mixed
    {
        return $export->download(
            activity: $activity,
            registrationIds: $data['registration_ids'] ?? [],
            columnKeys: $data['column_keys'] ?? [],
        );
    }
}
