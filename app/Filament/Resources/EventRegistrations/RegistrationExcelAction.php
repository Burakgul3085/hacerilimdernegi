<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Support\RegistrationExportPlan;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

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
            ->schema(fn (Activity $record): array => self::activitySchema($record))
            ->action(function (array $data, Activity $record, ExportEventRegistrations $export) {
                return self::downloadActivity($data, $record, $export);
            });
    }

    private static function forActivity(string $name, Activity $activity): Action
    {
        return Action::make($name)
            ->label('Excel\'e aktar')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading($activity->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez. Başvuru numarası her dosyada durur.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => self::activityDefaults($activity))
            ->schema(self::activitySchema($activity))
            ->action(function (array $data, ExportEventRegistrations $export) use ($activity) {
                return self::downloadActivity($data, $activity, $export);
            });
    }

    private static function forUnassigned(string $name): Action
    {
        $columnOptions = RegistrationExportPlan::plainColumnOptions();

        return Action::make($name)
            ->label('Excel indir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading('Diğer başvurular')
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => [
                'status' => null,
                'registration_ids' => array_keys(self::registrationOptions(unassignedOnly: true)),
                'column_keys' => array_keys($columnOptions),
            ])
            ->schema([
                self::statusSelect(fn (mixed $state, Set $set): mixed => $set(
                    'registration_ids',
                    array_keys(self::registrationOptions(status: filled($state) ? ApplicationStatus::from((string) $state) : null, unassignedOnly: true)),
                )),
                self::peopleList(fn (Get $get): array => self::registrationOptions(
                    status: filled($get('status')) ? ApplicationStatus::from((string) $get('status')) : null,
                    unassignedOnly: true,
                )),
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
            'registration_ids' => array_keys(self::registrationOptions(activity: $activity)),
            'column_keys' => array_keys(RegistrationExportPlan::columnOptions($activity, includeLegacyNotes: true)),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function activitySchema(Activity $activity): array
    {
        $columnOptions = RegistrationExportPlan::columnOptions($activity, includeLegacyNotes: true);

        return [
            self::statusSelect(function (mixed $state, Set $set) use ($activity): mixed {
                return $set(
                    'registration_ids',
                    array_keys(self::registrationOptions(
                        activity: $activity,
                        status: filled($state) ? ApplicationStatus::from((string) $state) : null,
                    )),
                );
            }),
            self::peopleList(fn (Get $get): array => self::registrationOptions(
                activity: $activity,
                status: filled($get('status')) ? ApplicationStatus::from((string) $get('status')) : null,
            )),
            self::columnsList($columnOptions),
        ];
    }

    private static function statusSelect(\Closure $afterStateUpdated): Select
    {
        return Select::make('status')
            ->label('Durum')
            ->placeholder('Tümü')
            ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                fn (ApplicationStatus $status): array => [$status->value => $status->label()],
            ))
            ->live()
            ->afterStateUpdated($afterStateUpdated);
    }

    /**
     * @param  \Closure(): array<string, string>  $options
     */
    private static function peopleList(\Closure $options): CheckboxList
    {
        return CheckboxList::make('registration_ids')
            ->label('Başvurular')
            ->options($options)
            ->searchable()
            ->bulkToggleable()
            ->columns(1)
            ->required()
            ->minItems(1)
            ->helperText('Tümünü seç / seçimi temizle ile hızlıca daraltın.');
    }

    /**
     * @param  array<string, string>  $options
     */
    private static function columnsList(array $options): CheckboxList
    {
        return CheckboxList::make('column_keys')
            ->label('Excel alanları')
            ->options($options)
            ->searchable()
            ->bulkToggleable()
            ->columns(2)
            ->required()
            ->minItems(1)
            ->helperText('Başvuru numarası her dosyada otomatik durur.');
    }

    /**
     * @return array<string, string>
     */
    private static function registrationOptions(
        ?Activity $activity = null,
        ?ApplicationStatus $status = null,
        bool $unassignedOnly = false,
    ): array {
        return EventRegistration::query()
            ->when($activity !== null, fn (Builder $query): Builder => $query->forActivity($activity))
            ->when($unassignedOnly, fn (Builder $query): Builder => $query->unassigned())
            ->when($status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (EventRegistration $registration): array {
                $label = trim($registration->name.' · '.$registration->email.' · #'.$registration->getKey());

                return [(string) $registration->getKey() => $label];
            })
            ->all();
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
