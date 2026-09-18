<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;

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
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(fn (Activity $record): string => $record->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (Activity $record): array => RegistrationExportForm::activityDefaults($record))
            ->schema(fn (Activity $record): array => RegistrationExportForm::activitySchema(
                (int) $record->getKey(),
                'Kurumsal Excel’e yalnızca işaretli satırlar yazılır.',
                'Excel alanları',
            ))
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
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading($activity->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez. Başvuru numarası her dosyada durur.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => RegistrationExportForm::activityDefaults(Activity::query()->findOrFail($activityId)))
            ->schema(fn (): array => RegistrationExportForm::activitySchema(
                $activityId,
                'Kurumsal Excel’e yalnızca işaretli satırlar yazılır.',
                'Excel alanları',
            ))
            ->action(function (array $data, ExportEventRegistrations $export) use ($activityId) {
                return self::downloadActivity($data, Activity::query()->findOrFail($activityId), $export);
            });
    }

    private static function forUnassigned(string $name): Action
    {
        return Action::make($name)
            ->label('Excel indir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Diğer başvurular')
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar iner. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->fillForm(fn (): array => RegistrationExportForm::unassignedDefaults())
            ->schema(fn (): array => RegistrationExportForm::unassignedSchema(
                'Kurumsal Excel’e yalnızca işaretli satırlar yazılır.',
                'Excel alanları',
            ))
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
